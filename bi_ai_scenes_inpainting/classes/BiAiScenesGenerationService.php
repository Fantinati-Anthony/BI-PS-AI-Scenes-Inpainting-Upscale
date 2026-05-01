<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Generation orchestrator - dispatches a request to the right Replicate
 * provider, persists the resulting image into BiAiScenesRender, logs.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesGenerationService
{
    /** @var Module */
    private $module;
    /** @var BiAiScenesHttpClient */
    private $http;
    /** @var BiAiScenesImageManager */
    private $images;

    public function __construct(Module $module)
    {
        $this->module = $module;
        $this->http = new BiAiScenesHttpClient();
        $this->images = new BiAiScenesImageManager($module);
    }

    /**
     * @param string $key
     *
     * @return BiAiScenesApiInterface|null
     */
    public function provider($key)
    {
        $map = BiAiScenesConfiguration::providerMap();
        if (!isset($map[$key]) || !class_exists($map[$key])) {
            return null;
        }
        $class = $map[$key];

        return new $class($this->http);
    }

    /**
     * Start a generation. Returns either an immediate result (image stored)
     * or a prediction_id for client-side polling.
     *
     * @param string $providerKey
     * @param array $params
     * @param array $context {id_product, id_product_attribute, id_image}
     *
     * @return array
     */
    public function start($providerKey, array $params, array $context = [])
    {
        $provider = $this->provider($providerKey);
        if (!$provider) {
            return ['success' => false, 'error' => 'Unknown provider: ' . $providerKey];
        }

        $render = new BiAiScenesRender();
        $render->id_product = (int) (isset($context['id_product']) ? $context['id_product'] : 0);
        $render->id_product_attribute = (int) (isset($context['id_product_attribute']) ? $context['id_product_attribute'] : 0);
        $render->id_image = (int) (isset($context['id_image']) ? $context['id_image'] : 0);
        $render->operation = $provider->getOperation();
        $render->provider_key = $provider->getKey();
        $render->prompt = isset($params['prompt']) ? Tools::substr((string) $params['prompt'], 0, 4000) : '';
        $render->negative_prompt = isset($params['negative_prompt']) ? Tools::substr((string) $params['negative_prompt'], 0, 2000) : '';
        $render->source_image_url = isset($params['image_url']) ? Tools::substr((string) $params['image_url'], 0, 2000) : '';
        $render->mask_filename = isset($params['mask_filename']) ? (string) $params['mask_filename'] : '';
        $render->status = BiAiScenesRender::STATUS_PROCESSING;
        $render->params_json = json_encode($params);
        $render->id_shop = (int) Context::getContext()->shop->id;
        $render->date_add = date('Y-m-d H:i:s');
        $render->date_upd = date('Y-m-d H:i:s');
        $render->add();

        $result = $provider->generate($params);

        if (!empty($result['prediction_id'])) {
            $render->prediction_id = (string) $result['prediction_id'];
            $render->update();
        }

        if (!empty($result['success']) && !empty($result['image_url'])) {
            return $this->finalize($render, $result['image_url']);
        }

        if (!empty($result['error'])) {
            $render->status = BiAiScenesRender::STATUS_FAILED;
            $render->error_message = Tools::substr((string) $result['error'], 0, 2000);
            $render->update();
            $this->log($render, 'generate', 'failed', $result['error']);

            return ['success' => false, 'error' => $result['error']];
        }

        $this->log($render, 'generate', 'started', null);

        return [
            'success' => false,
            'pending' => true,
            'prediction_id' => isset($result['prediction_id']) ? $result['prediction_id'] : null,
            'id_render' => (int) $render->id_bi_ai_scenes_render,
        ];
    }

    /**
     * Poll a pending prediction.
     *
     * @param string $predictionId
     *
     * @return array
     */
    public function poll($predictionId)
    {
        $render = BiAiScenesRender::findByPredictionId($predictionId);
        if (!$render || !Validate::isLoadedObject($render)) {
            return ['success' => false, 'error' => 'Unknown prediction'];
        }
        $provider = $this->provider($render->provider_key);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider missing for ' . $render->provider_key];
        }
        $status = $provider->getStatus($predictionId);

        if ($status['status'] === 'succeeded') {
            $url = $this->extractFirstUrl(isset($status['output']) ? $status['output'] : null);
            if (!$url) {
                $render->status = BiAiScenesRender::STATUS_FAILED;
                $render->error_message = 'No output URL';
                $render->update();

                return ['success' => false, 'error' => 'No output URL'];
            }

            return $this->finalize($render, $url);
        }

        if (in_array($status['status'], ['failed', 'canceled'], true)) {
            $render->status = $status['status'] === 'canceled' ? BiAiScenesRender::STATUS_CANCELED : BiAiScenesRender::STATUS_FAILED;
            $render->error_message = isset($status['error']) ? Tools::substr((string) $status['error'], 0, 2000) : '';
            $render->update();
            $this->log($render, 'poll', $status['status'], $render->error_message);

            return ['success' => false, 'status' => $status['status'], 'error' => $render->error_message];
        }

        return ['success' => false, 'status' => $status['status'], 'pending' => true];
    }

    /**
     * Cancel a pending prediction.
     *
     * @param string $predictionId
     *
     * @return bool
     */
    public function cancel($predictionId)
    {
        $render = BiAiScenesRender::findByPredictionId($predictionId);
        if (!$render || !Validate::isLoadedObject($render)) {
            return false;
        }
        $provider = $this->provider($render->provider_key);
        if (!$provider) {
            return false;
        }
        $ok = $provider->cancel($predictionId);
        if ($ok) {
            $render->status = BiAiScenesRender::STATUS_CANCELED;
            $render->update();
            $this->log($render, 'cancel', 'canceled', null);
        }

        return $ok;
    }

    /**
     * Download the final URL into local storage and mark the render succeeded.
     *
     * @param BiAiScenesRender $render
     * @param string $url
     *
     * @return array
     */
    private function finalize(BiAiScenesRender $render, $url)
    {
        $stored = $this->images->storeRenderFromUrl($url, $this->http);
        if (!$stored) {
            $render->status = BiAiScenesRender::STATUS_FAILED;
            $render->error_message = 'Image download failed';
            $render->update();

            return ['success' => false, 'error' => 'Image download failed'];
        }
        $render->image_filename = $stored['filename'];
        $render->image_path = $stored['path'];
        $render->file_size = (int) $stored['size'];
        $render->status = BiAiScenesRender::STATUS_SUCCEEDED;
        $render->update();
        $this->log($render, 'generate', 'succeeded', null);

        return [
            'success' => true,
            'status' => 'succeeded',
            'image_url' => $stored['url'],
            'id_render' => (int) $render->id_bi_ai_scenes_render,
            'prediction_id' => (string) $render->prediction_id,
        ];
    }

    /**
     * @param mixed $output
     *
     * @return string|null
     */
    private function extractFirstUrl($output)
    {
        if (is_string($output)) {
            return $output;
        }
        if (is_array($output)) {
            foreach (['image', 'images', 'output', 'image_url'] as $k) {
                if (!empty($output[$k]) && is_string($output[$k])) {
                    return $output[$k];
                }
                if (!empty($output[$k]) && is_array($output[$k]) && !empty($output[$k][0])) {
                    return (string) $output[$k][0];
                }
            }
            foreach ($output as $v) {
                if (is_string($v) && filter_var($v, FILTER_VALIDATE_URL)) {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * @param BiAiScenesRender $render
     * @param string $action
     * @param string $status
     * @param string|null $message
     */
    private function log(BiAiScenesRender $render, $action, $status, $message)
    {
        Db::getInstance()->insert('bi_ai_scenes_generation_log', [
            'id_product' => (int) $render->id_product,
            'id_product_attribute' => (int) $render->id_product_attribute,
            'operation' => pSQL((string) $render->operation),
            'provider_key' => pSQL((string) $render->provider_key),
            'action' => pSQL((string) $action),
            'status' => pSQL((string) $status),
            'message' => pSQL((string) ($message ?: ''), true),
            'api_response' => '',
            'id_employee' => (int) (Context::getContext()->employee ? Context::getContext()->employee->id : 0),
            'id_shop' => (int) Context::getContext()->shop->id,
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }
}

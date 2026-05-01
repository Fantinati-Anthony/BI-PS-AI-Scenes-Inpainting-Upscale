<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Generate admin controller - the main workspace for scene staging,
 * inpainting and upscaling. Hosts the batch table, modal, mask drawer,
 * before/after slider, and AJAX endpoints.
 *
 * AJAX actions (POST 'ajax_action'):
 *   - generate          : start a new render
 *   - poll              : poll a pending prediction by id
 *   - cancel            : cancel a pending prediction
 *   - upload_mask       : persist a base64 mask drawn on the canvas
 *   - delete            : delete a render (file + DB row)
 *   - test_credentials  : check Replicate API key
 *   - list              : list existing renders (filterable)
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBiAiScenesGenerateController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->module->l('Scenes / Inpaint / Upscale', 'admingenerate');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin-generate.js');
    }

    public function postProcess()
    {
        if (Tools::getValue('ajax_action')) {
            $this->ajaxDispatch(Tools::getValue('ajax_action'));
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        $providers = [
            'scene' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_SCENE),
            'inpaint' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_INPAINT),
            'upscale' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_UPSCALE),
        ];
        $defaults = [
            'scene' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_SCENE),
            'inpaint' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_INPAINT),
            'upscale' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_UPSCALE),
            'prompt' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_PROMPT),
            'negative_prompt' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_NEGATIVE_PROMPT),
            'aspect_ratio' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_ASPECT_RATIO),
            'output_format' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_OUTPUT_FORMAT),
            'upscale_factor' => (int) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_UPSCALE_FACTOR),
        ];
        $this->context->smarty->assign([
            'scenes_providers' => $providers,
            'scenes_defaults' => $defaults,
            'scenes_ajax_url' => $this->context->link->getAdminLink('AdminBiAiScenesGenerate'),
            'scenes_token' => Tools::getAdminTokenLite('AdminBiAiScenesGenerate'),
            'scenes_brush_color' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_MASK_BRUSH_COLOR),
            'scenes_brush_opacity' => (float) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_MASK_BRUSH_OPACITY),
        ]);
        $this->content = $this->createTemplate('generate.tpl')->fetch();
        parent::initContent();
    }

    /**
     * @param string $action
     */
    private function ajaxDispatch($action)
    {
        header('Content-Type: application/json');
        $service = new BiAiScenesGenerationService($this->module);
        $manager = new BiAiScenesImageManager($this->module);
        $resp = ['success' => false, 'error' => 'Unknown action'];
        try {
            switch ($action) {
                case 'test_credentials':
                    $http = new BiAiScenesHttpClient();
                    $r = $http->get('https://api.replicate.com/v1/account');
                    $resp = $r['success']
                        ? ['success' => true, 'username' => isset($r['data']['username']) ? $r['data']['username'] : '?']
                        : ['success' => false, 'error' => isset($r['error']) ? $r['error'] : 'Unknown'];
                    break;
                case 'generate':
                    $providerKey = (string) Tools::getValue('provider');
                    $params = [
                        'prompt' => (string) Tools::getValue('prompt'),
                        'negative_prompt' => (string) Tools::getValue('negative_prompt'),
                        'image_url' => (string) Tools::getValue('image_url'),
                        'mask_url' => (string) Tools::getValue('mask_url'),
                        'mask_filename' => (string) Tools::getValue('mask_filename'),
                        'aspect_ratio' => (string) Tools::getValue('aspect_ratio'),
                        'output_format' => (string) Tools::getValue('output_format'),
                        'scale' => (int) Tools::getValue('scale'),
                        'seed' => Tools::getValue('seed') !== false && Tools::getValue('seed') !== '' ? (int) Tools::getValue('seed') : null,
                        'num_inference_steps' => (int) Tools::getValue('num_inference_steps'),
                        'guidance_scale' => (float) Tools::getValue('guidance_scale'),
                        'strength' => (float) Tools::getValue('strength'),
                    ];
                    foreach ($params as $k => $v) {
                        if ($v === '' || $v === null) {
                            unset($params[$k]);
                        }
                    }
                    $context = [
                        'id_product' => (int) Tools::getValue('id_product'),
                        'id_product_attribute' => (int) Tools::getValue('id_product_attribute'),
                        'id_image' => (int) Tools::getValue('id_image'),
                    ];
                    $resp = $service->start($providerKey, $params, $context);
                    break;
                case 'poll':
                    $resp = $service->poll((string) Tools::getValue('prediction_id'));
                    break;
                case 'cancel':
                    $ok = $service->cancel((string) Tools::getValue('prediction_id'));
                    $resp = ['success' => (bool) $ok];
                    break;
                case 'upload_mask':
                    $stored = $manager->storeMaskBase64((string) Tools::getValue('data'));
                    if ($stored) {
                        $resp = ['success' => true, 'url' => $stored['url'], 'filename' => $stored['filename']];
                    } else {
                        $resp = ['success' => false, 'error' => 'Failed to save mask'];
                    }
                    break;
                case 'delete':
                    $id = (int) Tools::getValue('id_render');
                    $render = new BiAiScenesRender($id);
                    if (!Validate::isLoadedObject($render)) {
                        $resp = ['success' => false, 'error' => 'Not found'];
                        break;
                    }
                    $manager->delete($render);
                    $render->delete();
                    $resp = ['success' => true];
                    break;
                case 'list':
                    $op = (string) Tools::getValue('operation');
                    $sql = new DbQuery();
                    $sql->select('*')
                        ->from('bi_ai_scenes_renders')
                        ->where('id_shop = ' . (int) $this->context->shop->id)
                        ->orderBy('date_add DESC')
                        ->limit(100);
                    if ($op !== '') {
                        $sql->where("operation = '" . pSQL($op) . "'");
                    }
                    $rows = Db::getInstance()->executeS($sql) ?: [];
                    foreach ($rows as &$r) {
                        $r['image_url'] = !empty($r['image_filename'])
                            ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $r['image_filename'])
                            : '';
                    }
                    $resp = ['success' => true, 'rows' => $rows];
                    break;
            }
        } catch (Exception $e) {
            $resp = ['success' => false, 'error' => $e->getMessage()];
        }
        echo json_encode($resp);
        exit;
    }
}

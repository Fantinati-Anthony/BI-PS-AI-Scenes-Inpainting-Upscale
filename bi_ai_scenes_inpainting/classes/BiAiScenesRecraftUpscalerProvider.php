<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Recraft Crisp Upscaler via Replicate.
 *
 * Model: recraft-ai/recraft-crisp-upscaled
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesRecraftUpscalerProvider implements BiAiScenesApiInterface
{
    use BiAiScenesReplicateProviderTrait;

    public const MODEL = 'recraft-ai/recraft-crisp-upscaled';

    public function getName()
    {
        return 'Replicate Recraft Crisp Upscaler';
    }

    public function getKey()
    {
        return 'recraft_upscaler';
    }

    public function getOperation()
    {
        return self::OP_UPSCALE;
    }

    public function generate(array $params)
    {
        if (empty($params['image_url'])) {
            return ['success' => false, 'error' => 'image_url is required for upscale'];
        }

        $input = isset($params['extra']) && is_array($params['extra']) ? $params['extra'] : [];
        $input['image'] = $params['image_url'];

        return $this->submitPrediction(self::MODEL, $input);
    }
}

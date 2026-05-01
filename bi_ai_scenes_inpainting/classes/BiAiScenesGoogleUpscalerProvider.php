<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Google Upscaler via Replicate.
 *
 * Model: google/upscaler
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesGoogleUpscalerProvider implements BiAiScenesApiInterface
{
    use BiAiScenesReplicateProviderTrait;

    public const MODEL = 'google/upscaler';

    public function getName()
    {
        return 'Replicate Google Upscaler';
    }

    public function getKey()
    {
        return 'google_upscaler';
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

        if (isset($params['scale'])) {
            $input['upscale_factor'] = max(2, min(8, (int) $params['scale']));
        }
        if (isset($params['compression_quality'])) {
            $input['compression_quality'] = max(50, min(100, (int) $params['compression_quality']));
        }

        return $this->submitPrediction(self::MODEL, $input);
    }
}

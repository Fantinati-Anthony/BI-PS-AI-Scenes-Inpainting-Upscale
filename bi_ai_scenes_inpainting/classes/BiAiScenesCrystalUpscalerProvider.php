<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Crystal Upscaler via Replicate.
 *
 * Model: philz1337x/crystal-upscaler
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesCrystalUpscalerProvider implements BiAiScenesApiInterface
{
    use BiAiScenesReplicateProviderTrait;

    public const MODEL = 'philz1337x/crystal-upscaler';

    public function getName()
    {
        return 'Replicate Crystal Upscaler';
    }

    public function getKey()
    {
        return 'crystal_upscaler';
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
            $input['scale'] = max(1.0, min(8.0, (float) $params['scale']));
        }
        if (isset($params['creativity'])) {
            $input['creativity'] = max(0.0, min(1.0, (float) $params['creativity']));
        }
        if (isset($params['resemblance'])) {
            $input['resemblance'] = max(0.0, min(3.0, (float) $params['resemblance']));
        }
        if (isset($params['hdr'])) {
            $input['hdr'] = max(0.0, min(1.0, (float) $params['hdr']));
        }
        if (isset($params['seed'])) {
            $input['seed'] = (int) $params['seed'];
        }

        return $this->submitPrediction(self::MODEL, $input);
    }
}

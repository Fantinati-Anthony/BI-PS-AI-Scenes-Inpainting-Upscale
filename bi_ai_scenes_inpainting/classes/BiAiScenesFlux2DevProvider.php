<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * FLUX-2 Dev - lighter / faster scene staging variant via Replicate.
 *
 * Model: black-forest-labs/flux-2-dev
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesFlux2DevProvider implements BiAiScenesApiInterface
{
    use BiAiScenesReplicateProviderTrait;

    public const MODEL = 'black-forest-labs/flux-2-dev';

    public function getName()
    {
        return 'Replicate FLUX-2 Dev';
    }

    public function getKey()
    {
        return 'flux2_dev';
    }

    public function getOperation()
    {
        return self::OP_SCENE;
    }

    public function generate(array $params)
    {
        $input = isset($params['extra']) && is_array($params['extra']) ? $params['extra'] : [];

        if (!empty($params['prompt'])) {
            $input['prompt'] = (string) $params['prompt'];
        }
        if (!empty($params['image_url'])) {
            $input['image_prompt'] = $params['image_url'];
        }
        if (!empty($params['negative_prompt'])) {
            $input['negative_prompt'] = (string) $params['negative_prompt'];
        }
        if (isset($params['aspect_ratio'])) {
            $input['aspect_ratio'] = (string) $params['aspect_ratio'];
        }
        if (isset($params['output_format'])) {
            $input['output_format'] = (string) $params['output_format'];
        }
        if (isset($params['seed'])) {
            $input['seed'] = (int) $params['seed'];
        }
        if (isset($params['num_outputs'])) {
            $input['num_outputs'] = max(1, min(4, (int) $params['num_outputs']));
        }

        return $this->submitPrediction(self::MODEL, $input);
    }
}

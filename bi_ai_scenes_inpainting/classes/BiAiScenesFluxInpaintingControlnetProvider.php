<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * FLUX-Dev Inpainting + ControlNet via Replicate.
 *
 * Model: zsxkib/flux-dev-inpainting-controlnet
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesFluxInpaintingControlnetProvider implements BiAiScenesApiInterface
{
    use BiAiScenesReplicateProviderTrait;

    public const MODEL = 'zsxkib/flux-dev-inpainting-controlnet';

    public function getName()
    {
        return 'Replicate FLUX-Dev Inpainting + ControlNet';
    }

    public function getKey()
    {
        return 'flux_inpainting_cn';
    }

    public function getOperation()
    {
        return self::OP_INPAINT;
    }

    public function generate(array $params)
    {
        if (empty($params['image_url']) || empty($params['mask_url'])) {
            return ['success' => false, 'error' => 'image_url and mask_url are required for inpainting'];
        }

        $input = isset($params['extra']) && is_array($params['extra']) ? $params['extra'] : [];
        $input['image'] = $params['image_url'];
        $input['mask'] = $params['mask_url'];

        if (!empty($params['control_image_url'])) {
            $input['control_image'] = $params['control_image_url'];
        }
        if (!empty($params['control_type'])) {
            $input['control_type'] = (string) $params['control_type'];
        }
        if (isset($params['controlnet_conditioning_scale'])) {
            $input['controlnet_conditioning_scale'] = max(0.0, min(2.0, (float) $params['controlnet_conditioning_scale']));
        }
        if (!empty($params['prompt'])) {
            $input['prompt'] = (string) $params['prompt'];
        }
        if (isset($params['strength'])) {
            $input['strength'] = max(0.0, min(1.0, (float) $params['strength']));
        }
        if (isset($params['num_inference_steps'])) {
            $input['num_inference_steps'] = max(1, min(50, (int) $params['num_inference_steps']));
        }
        if (isset($params['guidance_scale'])) {
            $input['guidance_scale'] = max(1.0, min(20.0, (float) $params['guidance_scale']));
        }
        if (isset($params['seed'])) {
            $input['seed'] = (int) $params['seed'];
        }

        return $this->submitPrediction(self::MODEL, $input);
    }
}

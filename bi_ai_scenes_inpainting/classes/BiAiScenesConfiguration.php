<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Centralized configuration keys, defaults, and helpers.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesConfiguration
{
    /* API */
    public const KEY_API_KEY = 'SCENES_REPLICATE_API_KEY';
    public const KEY_PROVIDER_SCENE = 'SCENES_PROVIDER_SCENE';
    public const KEY_PROVIDER_INPAINT = 'SCENES_PROVIDER_INPAINT';
    public const KEY_PROVIDER_UPSCALE = 'SCENES_PROVIDER_UPSCALE';

    /* Processing mode */
    public const KEY_PROCESSING_MODE = 'SCENES_PROCESSING_MODE';

    /* Defaults */
    public const KEY_DEFAULT_PROMPT = 'SCENES_DEFAULT_PROMPT';
    public const KEY_DEFAULT_NEGATIVE_PROMPT = 'SCENES_DEFAULT_NEGATIVE_PROMPT';
    public const KEY_DEFAULT_ASPECT_RATIO = 'SCENES_DEFAULT_ASPECT_RATIO';
    public const KEY_DEFAULT_OUTPUT_FORMAT = 'SCENES_DEFAULT_OUTPUT_FORMAT';
    public const KEY_DEFAULT_UPSCALE_FACTOR = 'SCENES_DEFAULT_UPSCALE_FACTOR';

    /* Rate limit / quotas */
    public const KEY_RATE_LIMIT = 'SCENES_RATE_LIMIT';
    public const KEY_RATE_WINDOW = 'SCENES_RATE_WINDOW';
    public const KEY_MAX_RUNTIME_SECONDS = 'SCENES_MAX_RUNTIME_SECONDS';
    public const KEY_MAX_INPUT_SIZE = 'SCENES_MAX_INPUT_SIZE';

    /* Display */
    public const KEY_DISPLAY_MODE = 'SCENES_DISPLAY_MODE';
    public const KEY_HOOK_POSITION = 'SCENES_HOOK_POSITION';
    public const KEY_BUTTON_LABEL = 'SCENES_BUTTON_LABEL';
    public const KEY_BUTTON_BG_COLOR = 'SCENES_BUTTON_BG_COLOR';
    public const KEY_BUTTON_TEXT_COLOR = 'SCENES_BUTTON_TEXT_COLOR';

    /* Theme / accent */
    public const KEY_ACCENT_COLOR = 'SCENES_ACCENT_COLOR';
    public const KEY_MODAL_BG_COLOR = 'SCENES_MODAL_BG_COLOR';
    public const KEY_MODAL_HEADER_BG = 'SCENES_MODAL_HEADER_BG';
    public const KEY_MODAL_HEADER_TEXT = 'SCENES_MODAL_HEADER_TEXT';
    public const KEY_MODAL_BORDER_RADIUS = 'SCENES_MODAL_BORDER_RADIUS';
    public const KEY_MODAL_BORDER_WIDTH = 'SCENES_MODAL_BORDER_WIDTH';
    public const KEY_MODAL_BORDER_COLOR = 'SCENES_MODAL_BORDER_COLOR';

    /* Mask brush */
    public const KEY_MASK_BRUSH_COLOR = 'SCENES_MASK_BRUSH_COLOR';
    public const KEY_MASK_BRUSH_OPACITY = 'SCENES_MASK_BRUSH_OPACITY';

    /* CSP */
    public const KEY_CSP_ENABLED = 'SCENES_CSP_ENABLED';

    public static function defaults()
    {
        return [
            self::KEY_API_KEY => '',
            self::KEY_PROVIDER_SCENE => 'flux2_dev',
            self::KEY_PROVIDER_INPAINT => 'flux_inpainting',
            self::KEY_PROVIDER_UPSCALE => 'google_upscaler',
            self::KEY_PROCESSING_MODE => 'realtime',
            self::KEY_DEFAULT_PROMPT => '',
            self::KEY_DEFAULT_NEGATIVE_PROMPT => 'low quality, blurry, distorted, watermark',
            self::KEY_DEFAULT_ASPECT_RATIO => '1:1',
            self::KEY_DEFAULT_OUTPUT_FORMAT => 'png',
            self::KEY_DEFAULT_UPSCALE_FACTOR => 4,
            self::KEY_RATE_LIMIT => 30,
            self::KEY_RATE_WINDOW => 60,
            self::KEY_MAX_RUNTIME_SECONDS => 300,
            self::KEY_MAX_INPUT_SIZE => 12 * 1024 * 1024,
            self::KEY_DISPLAY_MODE => 'modal',
            self::KEY_HOOK_POSITION => 'displayProductActions',
            self::KEY_BUTTON_LABEL => 'AI scenes',
            self::KEY_BUTTON_BG_COLOR => '#4f46e5',
            self::KEY_BUTTON_TEXT_COLOR => '#ffffff',
            self::KEY_ACCENT_COLOR => '#4f46e5',
            self::KEY_MODAL_BG_COLOR => '#ffffff',
            self::KEY_MODAL_HEADER_BG => '#0f172a',
            self::KEY_MODAL_HEADER_TEXT => '#ffffff',
            self::KEY_MODAL_BORDER_RADIUS => 12,
            self::KEY_MODAL_BORDER_WIDTH => 1,
            self::KEY_MODAL_BORDER_COLOR => '#4f46e5',
            self::KEY_MASK_BRUSH_COLOR => '#ff3366',
            self::KEY_MASK_BRUSH_OPACITY => 0.6,
            self::KEY_CSP_ENABLED => 0,
        ];
    }

    public static function get($key)
    {
        $val = Configuration::get($key);
        if ($val === false || $val === null || $val === '') {
            $defaults = self::defaults();

            return isset($defaults[$key]) ? $defaults[$key] : null;
        }

        return $val;
    }

    public static function set($key, $value)
    {
        return Configuration::updateValue($key, $value);
    }

    public static function applyDefaults()
    {
        foreach (self::defaults() as $k => $v) {
            $current = Configuration::get($k);
            if ($current === false || $current === null || $current === '') {
                Configuration::updateValue($k, $v);
            }
        }
    }

    public static function providerMap()
    {
        return [
            'flux2_pro' => 'BiAiScenesFlux2ProProvider',
            'flux2_dev' => 'BiAiScenesFlux2DevProvider',
            'sd_inpainting' => 'BiAiScenesSdInpaintingProvider',
            'flux_inpainting' => 'BiAiScenesFluxInpaintingProvider',
            'flux_inpainting_cn' => 'BiAiScenesFluxInpaintingControlnetProvider',
            'google_upscaler' => 'BiAiScenesGoogleUpscalerProvider',
            'crystal_upscaler' => 'BiAiScenesCrystalUpscalerProvider',
            'recraft_upscaler' => 'BiAiScenesRecraftUpscalerProvider',
        ];
    }

    public static function providersForOperation($operation)
    {
        $map = self::providerMap();
        $out = [];
        $http = new BiAiScenesHttpClient();
        foreach ($map as $key => $class) {
            if (!class_exists($class)) {
                continue;
            }
            /** @var BiAiScenesApiInterface $instance */
            $instance = new $class($http);
            if ($instance->getOperation() === $operation) {
                $out[$key] = $instance->getName();
            }
        }

        return $out;
    }
}

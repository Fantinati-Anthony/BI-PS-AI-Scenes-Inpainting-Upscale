<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * API Provider Interface - Abstraction for Replicate-based image operations.
 *
 * Implementations cover three operation families:
 *   - "scene"   : text-to-image / product staging (FLUX-2 pro & dev, ...)
 *   - "inpaint" : mask-based local edit (SD inpainting, FLUX inpainting, ...)
 *   - "upscale" : super-resolution (Google, Crystal, Recraft, ...)
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

interface BiAiScenesApiInterface
{
    public const OP_SCENE = 'scene';
    public const OP_INPAINT = 'inpaint';
    public const OP_UPSCALE = 'upscale';

    public const PREDICTIONS_URL = 'https://api.replicate.com/v1/predictions';

    /**
     * Human-readable provider name (e.g. "Replicate FLUX-2 Pro").
     *
     * @return string
     */
    public function getName();

    /**
     * Provider identifier key (e.g. "flux2_pro", "sd_inpainting", "google_upscaler").
     *
     * @return string
     */
    public function getKey();

    /**
     * Operation family this provider handles.
     *
     * @return string one of OP_SCENE, OP_INPAINT, OP_UPSCALE
     */
    public function getOperation();

    /**
     * Validate configured credentials by hitting Replicate /account.
     *
     * @return array{valid: bool, message: string}
     */
    public function validateCredentials();

    /**
     * Provider-specific admin form fields.
     *
     * @return array[]
     */
    public function getConfigFields();

    /**
     * Start a generation. For scenes, image_url is optional (text-to-image);
     * for inpaint, image_url + mask_url are required; for upscale image_url is
     * required.
     *
     * @param array $params {
     *     @var string|null $prompt        Text prompt (scene / inpaint)
     *     @var string|null $negative_prompt
     *     @var string|null $image_url     Source image URL
     *     @var string|null $mask_url      Mask URL (inpaint only)
     *     @var int|null    $scale         Upscale factor (upscale only)
     *     @var array       $extra         Provider-specific raw input override
     * }
     *
     * @return array{success: bool, image_url?: string, prediction_id?: string, error?: string|null, raw_output?: mixed}
     */
    public function generate(array $params);

    /**
     * Poll a prediction.
     *
     * @param string $predictionId
     *
     * @return array{status: string, output?: mixed, error?: string}
     */
    public function getStatus($predictionId);

    /**
     * Cancel a running prediction.
     *
     * @param string $predictionId
     *
     * @return bool
     */
    public function cancel($predictionId);

    /**
     * Download a remote file to a local path.
     *
     * @param string $url
     * @param string $localPath
     *
     * @return bool
     */
    public function downloadFile($url, $localPath);
}

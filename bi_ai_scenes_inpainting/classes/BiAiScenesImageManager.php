<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Image storage manager - persists generated images and masks under
 * views/img/custom/, returns front-end URLs.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesImageManager
{
    public const SUBDIR_RENDERS = 'renders';
    public const SUBDIR_MASKS = 'masks';

    /** @var Module */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Absolute base path for stored assets.
     *
     * @return string
     */
    public function getBasePath()
    {
        $path = _PS_MODULE_DIR_ . $this->module->name . '/views/img/custom/';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Absolute path for a sub-dir, created on demand.
     *
     * @param string $sub one of SUBDIR_*
     *
     * @return string
     */
    public function getSubPath($sub)
    {
        $sub = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $sub));
        $path = $this->getBasePath() . $sub . '/';
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Public URL for a stored asset given its filename.
     *
     * @param string $sub
     * @param string $filename
     *
     * @return string
     */
    public function getPublicUrl($sub, $filename)
    {
        $context = Context::getContext();
        $base = $context->shop->getBaseURL(true) . 'modules/' . $this->module->name . '/views/img/custom/';

        return $base . $sub . '/' . $filename;
    }

    /**
     * Save a binary blob (already-fetched image bytes) to the renders dir.
     *
     * @param string $bytes
     * @param string $extension e.g. "png", "jpg", "webp"
     *
     * @return array{path: string, url: string, filename: string, size: int}|null
     */
    public function storeRenderBytes($bytes, $extension = 'png')
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower((string) $extension)) ?: 'png';
        $filename = 'scene_' . date('Ymd_His') . '_' . substr(md5($bytes . microtime(true)), 0, 8) . '.' . $extension;
        $path = $this->getSubPath(self::SUBDIR_RENDERS) . $filename;
        if (file_put_contents($path, $bytes) === false) {
            return null;
        }

        return [
            'path' => $path,
            'url' => $this->getPublicUrl(self::SUBDIR_RENDERS, $filename),
            'filename' => $filename,
            'size' => filesize($path) ?: strlen($bytes),
        ];
    }

    /**
     * Download a remote image URL to the renders dir.
     *
     * @param string $url
     * @param BiAiScenesHttpClient $http
     *
     * @return array{path: string, url: string, filename: string, size: int}|null
     */
    public function storeRenderFromUrl($url, BiAiScenesHttpClient $http)
    {
        $extension = $this->guessExtension($url);
        $filename = 'scene_' . date('Ymd_His') . '_' . substr(md5($url), 0, 8) . '.' . $extension;
        $path = $this->getSubPath(self::SUBDIR_RENDERS) . $filename;
        if (!$http->downloadFile($url, $path)) {
            return null;
        }

        return [
            'path' => $path,
            'url' => $this->getPublicUrl(self::SUBDIR_RENDERS, $filename),
            'filename' => $filename,
            'size' => filesize($path) ?: 0,
        ];
    }

    /**
     * Persist a base64-encoded mask drawn on the canvas.
     *
     * @param string $base64Data Data URL or raw base64
     *
     * @return array{path: string, url: string, filename: string, size: int}|null
     */
    public function storeMaskBase64($base64Data)
    {
        if (strpos($base64Data, ',') !== false) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }
        $bytes = base64_decode($base64Data, true);
        if ($bytes === false) {
            return null;
        }
        $filename = 'mask_' . date('Ymd_His') . '_' . substr(md5($bytes), 0, 8) . '.png';
        $path = $this->getSubPath(self::SUBDIR_MASKS) . $filename;
        if (file_put_contents($path, $bytes) === false) {
            return null;
        }

        return [
            'path' => $path,
            'url' => $this->getPublicUrl(self::SUBDIR_MASKS, $filename),
            'filename' => $filename,
            'size' => filesize($path) ?: strlen($bytes),
        ];
    }

    /**
     * Delete an image (and best-effort sibling mask).
     *
     * @param BiAiScenesRender $render
     *
     * @return bool
     */
    public function delete(BiAiScenesRender $render)
    {
        $ok = true;
        if ($render->image_path && is_file($render->image_path)) {
            $ok = @unlink($render->image_path) && $ok;
        }
        if ($render->mask_filename) {
            $maskPath = $this->getSubPath(self::SUBDIR_MASKS) . $render->mask_filename;
            if (is_file($maskPath)) {
                @unlink($maskPath);
            }
        }

        return $ok;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function guessExtension($url)
    {
        if (preg_match('/\.(png|jpe?g|webp)(\?|$)/i', $url, $m)) {
            return strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        }

        return 'png';
    }
}

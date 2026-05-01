<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Module core trait - shared module-level helpers (paths, smarty cache).
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait BiAiScenesModuleCoreTrait
{
    /**
     * @return string
     */
    public function getCustomPath()
    {
        $p = _PS_MODULE_DIR_ . $this->name . '/views/img/custom/';
        if (!is_dir($p)) {
            @mkdir($p, 0755, true);
        }

        return $p;
    }

    /**
     * @return BiAiScenesGenerationService
     */
    public function getGenerationService()
    {
        return new BiAiScenesGenerationService($this);
    }

    /**
     * @return BiAiScenesRenderService
     */
    public function getRenderService()
    {
        return new BiAiScenesRenderService($this);
    }
}

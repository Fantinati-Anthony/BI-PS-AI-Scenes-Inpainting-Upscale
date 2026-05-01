<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Product-page rendering helpers for front-office hooks.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait ModuleRenderingProductTrait
{
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return array
     */
    protected function buildProductSceneVars($idProduct, $idProductAttribute = 0)
    {
        return $this->getRenderService()->getViewModel((int) $idProduct, (int) $idProductAttribute);
    }
}

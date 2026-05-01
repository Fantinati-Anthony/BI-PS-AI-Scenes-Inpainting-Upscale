<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Render service - resolves the latest succeeded render per product /
 * combination and exposes Smarty-friendly view models for the front-office
 * (modal, product tab, before/after slider).
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesRenderService
{
    /** @var Module */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Get a view model for a product's latest scene/upscale render.
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return array{has_render: bool, image_url?: string, source_url?: string, operation?: string, provider?: string}
     */
    public function getViewModel($idProduct, $idProductAttribute = 0)
    {
        $render = BiAiScenesRender::findLatestForProduct((int) $idProduct, (int) $idProductAttribute);
        if (!$render || !$render->image_filename) {
            return ['has_render' => false];
        }

        $manager = new BiAiScenesImageManager($this->module);

        return [
            'has_render' => true,
            'image_url' => $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $render->image_filename),
            'source_url' => (string) $render->source_image_url,
            'operation' => $render->operation,
            'provider' => $render->provider_key,
            'prompt' => (string) $render->prompt,
            'date' => $render->date_upd,
        ];
    }

    /**
     * List all renders for a product (admin product tab gallery).
     *
     * @param int $idProduct
     *
     * @return array<int,array<string,mixed>>
     */
    public function listForProduct($idProduct)
    {
        $rows = BiAiScenesRender::listForProduct((int) $idProduct);
        $manager = new BiAiScenesImageManager($this->module);
        foreach ($rows as &$row) {
            $row['public_url'] = !empty($row['image_filename'])
                ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $row['image_filename'])
                : '';
            $row['mask_url'] = !empty($row['mask_filename'])
                ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_MASKS, $row['mask_filename'])
                : '';
        }

        return $rows;
    }
}

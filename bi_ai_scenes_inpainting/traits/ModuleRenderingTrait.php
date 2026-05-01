<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Front-office rendering trait - injects CSS / JS into <head>.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait ModuleRenderingTrait
{
    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayHeader($params)
    {
        $this->context->controller->registerStylesheet(
            'bi-ai-scenes-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 200]
        );
        $this->context->controller->registerJavascript(
            'bi-ai-scenes-front',
            'modules/' . $this->name . '/views/js/front.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        return '';
    }
}

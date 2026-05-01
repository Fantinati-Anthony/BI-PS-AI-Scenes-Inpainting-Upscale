<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Optional CSP emission - emits a relaxed Content-Security-Policy when
 * SCENES_CSP_ENABLED is on, so the front-office can inline the modal styles
 * and load images coming from Replicate's delivery domain.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait BiAiScenesCspHookTrait
{
    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayHeader($params)
    {
        if (!(int) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_CSP_ENABLED)) {
            return '';
        }
        $cspFile = dirname(__FILE__) . '/../header_csp.txt';
        if (!is_readable($cspFile)) {
            return '';
        }
        $csp = trim((string) file_get_contents($cspFile));
        if ($csp === '') {
            return '';
        }

        return '<meta http-equiv="Content-Security-Policy" content="' . htmlspecialchars($csp, ENT_QUOTES) . '">';
    }
}

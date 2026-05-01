<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Parent admin tab - landing redirect to dashboard.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBiAiScenesParentController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminBiAiScenesDashboard'));
    }
}

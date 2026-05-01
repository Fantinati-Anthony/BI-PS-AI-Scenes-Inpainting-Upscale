<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Configuration admin controller - API key, provider defaults, theme, modal.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBiAiScenesConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->module->l('Configuration - BI AI Scenes', 'adminconfig');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitBiAiScenesConfig')) {
            foreach (BiAiScenesConfiguration::defaults() as $k => $_default) {
                if (Tools::getValue($k) !== false) {
                    BiAiScenesConfiguration::set($k, Tools::getValue($k));
                }
            }
            $this->confirmations[] = $this->module->l('Settings saved.', 'adminconfig');
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        $values = [];
        foreach (BiAiScenesConfiguration::defaults() as $k => $_default) {
            $values[$k] = (string) BiAiScenesConfiguration::get($k);
        }
        $this->context->smarty->assign([
            'scenes_values' => $values,
            'scenes_keys' => BiAiScenesConfiguration::class,
            'scenes_providers_scene' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_SCENE),
            'scenes_providers_inpaint' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_INPAINT),
            'scenes_providers_upscale' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_UPSCALE),
            'scenes_form_action' => $this->context->link->getAdminLink('AdminBiAiScenesConfig'),
        ]);

        $this->content = $this->createTemplate('configure.tpl')->fetch();
        parent::initContent();
    }
}

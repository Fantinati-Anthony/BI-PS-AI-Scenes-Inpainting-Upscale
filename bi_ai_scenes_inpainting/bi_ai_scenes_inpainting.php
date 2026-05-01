<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 *
 * Generate product scenes (mise en scène), inpaint local regions, and upscale
 * images using Replicate models (FLUX-2 pro/dev, SD-Inpainting, FLUX-Inpainting,
 * Google / Crystal / Recraft Upscalers). Mirrors the look-and-feel of the
 * BI 3D viewer module: same admin layout, batch table, modal, theme, languages.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Academic Free License 3.0 (AFL-3.0)
 *
 * Compatible PrestaShop 1.7.x - 9.x
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/BiAiScenesConfiguration.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesApiInterface.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesHttpClient.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesReplicateProviderTrait.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesFlux2ProProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesFlux2DevProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesSdInpaintingProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesFluxInpaintingProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesFluxInpaintingControlnetProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesGoogleUpscalerProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesCrystalUpscalerProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesRecraftUpscalerProvider.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesInstaller.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesGenerationService.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesRenderService.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesRender.php';
require_once dirname(__FILE__) . '/classes/BiAiScenesImageManager.php';

require_once dirname(__FILE__) . '/traits/BiAiScenesModuleCoreTrait.php';
require_once dirname(__FILE__) . '/traits/BiAiScenesModuleHooksTrait.php';
require_once dirname(__FILE__) . '/traits/ModuleRenderingTrait.php';
require_once dirname(__FILE__) . '/traits/ModuleRenderingProductTrait.php';
require_once dirname(__FILE__) . '/traits/ModuleWidgetTrait.php';
require_once dirname(__FILE__) . '/traits/BiAiScenesCspHookTrait.php';

class Bi_ai_scenes_inpainting extends Module
{
    use BiAiScenesModuleCoreTrait;
    use BiAiScenesModuleHooksTrait;
    use ModuleRenderingProductTrait;
    use ModuleWidgetTrait;

    use ModuleRenderingTrait, BiAiScenesCspHookTrait {
        ModuleRenderingTrait::hookDisplayHeader insteadof BiAiScenesCspHookTrait;
        ModuleRenderingTrait::hookDisplayHeader as private renderingHookDisplayHeader;
        BiAiScenesCspHookTrait::hookDisplayHeader as private cspHookDisplayHeader;
    }

    public function __construct()
    {
        $this->name = 'bi_ai_scenes_inpainting';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Anthony Fantinati - Blazing Ideas';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => '9.99.99'];
        $this->bootstrap = true;
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('BI - AI Scenes / Inpainting / Upscale');
        $this->description = $this->l('Generate product scenes, inpaint regions and upscale images using Replicate (FLUX-2, SD-Inpainting, FLUX-Inpainting, Google / Crystal / Recraft upscalers).');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? All generated scenes and history will be deleted.');

        if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
            $this->tabs = [
                [
                    'name' => 'BI - AI Scenes',
                    'class_name' => 'AdminBiAiScenesParent',
                    'visible' => true,
                    'icon' => 'auto_awesome',
                ],
                [
                    'name' => 'Dashboard',
                    'class_name' => 'AdminBiAiScenesDashboard',
                    'parent_class_name' => 'AdminBiAiScenesParent',
                    'visible' => true,
                    'icon' => 'dashboard',
                ],
                [
                    'name' => 'Configuration',
                    'class_name' => 'AdminBiAiScenesConfig',
                    'parent_class_name' => 'AdminBiAiScenesParent',
                    'visible' => true,
                    'icon' => 'settings',
                ],
                [
                    'name' => 'Scenes / Inpaint / Upscale',
                    'class_name' => 'AdminBiAiScenesGenerate',
                    'parent_class_name' => 'AdminBiAiScenesParent',
                    'visible' => true,
                    'icon' => 'photo_filter',
                ],
            ];
        }
    }

    public function install()
    {
        $installer = new BiAiScenesInstaller($this);
        $installer->cleanupOrphanedTabs();

        if (!parent::install()) {
            return false;
        }

        return $installer->install();
    }

    public function uninstall()
    {
        $installer = new BiAiScenesInstaller($this);
        $installer->uninstall();

        return parent::uninstall();
    }

    public function trans($string, array $parameters = [], $domain = null, $locale = null)
    {
        if ($domain !== null) {
            return parent::trans($string, $parameters, $domain, $locale);
        }

        static $subDomains = [
            'Modules.BiAiScenesInpainting.Admin',
            'Modules.BiAiScenesInpainting.AdminDashboard',
            'Modules.BiAiScenesInpainting.AdminGenerate',
            'Modules.BiAiScenesInpainting.AdminConfigA',
            'Modules.BiAiScenesInpainting.AdminConfigB',
            'Modules.BiAiScenesInpainting.AdminConfigC',
            'Modules.BiAiScenesInpainting.AdminConfigD',
            'Modules.BiAiScenesInpainting.ProductTab',
            'Modules.BiAiScenesInpainting.Shop',
        ];

        foreach ($subDomains as $subDomain) {
            $translated = parent::trans($string, $parameters, $subDomain, $locale);
            if ($translated !== $string) {
                return $translated;
            }
        }

        return $string;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminBiAiScenesDashboard'));
    }

    public function hookDisplayHeader($params = [])
    {
        $html = (string) $this->renderingHookDisplayHeader($params);
        $csp = (string) $this->cspHookDisplayHeader($params);

        return $html . $csp;
    }
}

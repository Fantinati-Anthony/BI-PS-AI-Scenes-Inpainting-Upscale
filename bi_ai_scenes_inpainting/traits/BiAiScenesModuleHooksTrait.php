<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Hooks trait - product page button + admin product extra tab.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

trait BiAiScenesModuleHooksTrait
{
    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayProductActions($params)
    {
        $idProduct = isset($params['product']['id_product']) ? (int) $params['product']['id_product'] : 0;
        if (!$idProduct && isset($params['id_product'])) {
            $idProduct = (int) $params['id_product'];
        }
        if (!$idProduct) {
            return '';
        }
        $vm = $this->getRenderService()->getViewModel($idProduct);
        if (empty($vm['has_render'])) {
            return '';
        }
        $this->context->smarty->assign([
            'scenes_view' => $vm,
            'scenes_button_label' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_BUTTON_LABEL),
            'scenes_button_bg' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_BUTTON_BG_COLOR),
            'scenes_button_fg' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_BUTTON_TEXT_COLOR),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_button.tpl');
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        return $this->hookDisplayProductActions($params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$idProduct) {
            return '';
        }
        $rows = $this->getRenderService()->listForProduct($idProduct);
        $this->context->smarty->assign([
            'scenes_product_rows' => $rows,
            'scenes_id_product' => $idProduct,
            'scenes_link_generate' => $this->context->link->getAdminLink('AdminBiAiScenesGenerate'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = (string) Tools::getValue('controller');
        if (in_array($controller, ['AdminProducts', 'AdminBiAiScenesGenerate', 'AdminBiAiScenesConfig', 'AdminBiAiScenesDashboard'], true)) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin.css');
        }
    }

    public function hookDisplayBackOfficeHeader()
    {
        return $this->hookActionAdminControllerSetMedia();
    }
}

<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Dashboard admin controller - status, counts, recent activity, credentials.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBiAiScenesDashboardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->module->l('Dashboard - BI AI Scenes', 'admindashboard');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }

    public function initContent()
    {
        $counts = BiAiScenesRender::countsByStatus();
        $recent = Db::getInstance()->executeS(
            'SELECT id_bi_ai_scenes_render, operation, provider_key, status, image_filename, date_add'
            . ' FROM ' . _DB_PREFIX_ . 'bi_ai_scenes_renders'
            . ' WHERE id_shop = ' . (int) $this->context->shop->id
            . ' ORDER BY date_add DESC LIMIT 20'
        ) ?: [];

        $manager = new BiAiScenesImageManager($this->module);
        foreach ($recent as &$row) {
            $row['image_url'] = !empty($row['image_filename'])
                ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $row['image_filename'])
                : '';
        }

        $apiKey = (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_API_KEY);
        $credentialsOk = false;
        $credentialsMsg = $this->module->l('Replicate API key not set yet.', 'admindashboard');
        if ($apiKey) {
            $http = new BiAiScenesHttpClient($apiKey);
            $resp = $http->get('https://api.replicate.com/v1/account');
            $credentialsOk = !empty($resp['success']);
            $credentialsMsg = $credentialsOk
                ? sprintf($this->module->l('Connected as %s', 'admindashboard'), isset($resp['data']['username']) ? $resp['data']['username'] : '?')
                : (isset($resp['error']) ? $resp['error'] : 'Unknown error');
        }

        $this->context->smarty->assign([
            'scenes_counts' => $counts,
            'scenes_recent' => $recent,
            'scenes_credentials_ok' => $credentialsOk,
            'scenes_credentials_msg' => $credentialsMsg,
            'scenes_link_config' => $this->context->link->getAdminLink('AdminBiAiScenesConfig'),
            'scenes_link_generate' => $this->context->link->getAdminLink('AdminBiAiScenesGenerate'),
        ]);

        $this->content = $this->createTemplate('dashboard.tpl')->fetch();
        parent::initContent();
    }
}

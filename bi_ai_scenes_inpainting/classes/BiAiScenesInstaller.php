<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Installer - SQL schema, hooks, default configuration, admin tabs.
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesInstaller
{
    public const HOOKS = [
        'displayHeader',
        'displayProductActions',
        'displayProductAdditionalInfo',
        'displayAdminProductsExtra',
        'actionAdminControllerSetMedia',
        'displayBackOfficeHeader',
    ];

    /** @var Module */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function install()
    {
        if (!$this->installSql()) {
            return false;
        }
        BiAiScenesConfiguration::applyDefaults();
        foreach (self::HOOKS as $h) {
            $this->module->registerHook($h);
        }
        $this->ensureCustomDir();
        $this->installLegacyTabs();

        return true;
    }

    public function uninstall()
    {
        $this->uninstallSql();
        $this->uninstallTabs();
        foreach (BiAiScenesConfiguration::defaults() as $k => $_v) {
            Configuration::deleteByName($k);
        }

        return true;
    }

    /**
     * Drop tabs from previous installs that may collide.
     */
    public function cleanupOrphanedTabs()
    {
        $classes = ['AdminBiAiScenesParent', 'AdminBiAiScenesDashboard', 'AdminBiAiScenesConfig', 'AdminBiAiScenesGenerate'];
        foreach ($classes as $c) {
            $idTab = (int) Tab::getIdFromClassName($c);
            if ($idTab) {
                $tab = new Tab($idTab);
                if (Validate::isLoadedObject($tab)) {
                    $tab->delete();
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function installSql()
    {
        $file = dirname(__FILE__) . '/../sql/install.sql';
        if (!is_readable($file)) {
            return false;
        }
        $sql = (string) file_get_contents($file);
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if (!Db::getInstance()->execute($stmt)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallSql()
    {
        $file = dirname(__FILE__) . '/../sql/uninstall.sql';
        if (!is_readable($file)) {
            return;
        }
        $sql = str_replace('PREFIX_', _DB_PREFIX_, (string) file_get_contents($file));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            Db::getInstance()->execute($stmt);
        }
    }

    private function ensureCustomDir()
    {
        $dirs = [
            _PS_MODULE_DIR_ . $this->module->name . '/views/img/custom/',
            _PS_MODULE_DIR_ . $this->module->name . '/views/img/custom/renders/',
            _PS_MODULE_DIR_ . $this->module->name . '/views/img/custom/masks/',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) {
                @mkdir($d, 0755, true);
            }
        }
    }

    /**
     * Install tabs for PS &lt; 9 (PS 9 uses $tabs property).
     */
    private function installLegacyTabs()
    {
        if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
            return; // handled by parent::install() reading $tabs
        }
        $parent = $this->createTab('AdminBiAiScenesParent', 'BI - AI Scenes', 0);
        if ($parent) {
            $this->createTab('AdminBiAiScenesDashboard', 'Dashboard', $parent);
            $this->createTab('AdminBiAiScenesConfig', 'Configuration', $parent);
            $this->createTab('AdminBiAiScenesGenerate', 'Scenes / Inpaint / Upscale', $parent);
        }
    }

    private function uninstallTabs()
    {
        foreach (['AdminBiAiScenesGenerate', 'AdminBiAiScenesConfig', 'AdminBiAiScenesDashboard', 'AdminBiAiScenesParent'] as $c) {
            $idTab = (int) Tab::getIdFromClassName($c);
            if ($idTab) {
                (new Tab($idTab))->delete();
            }
        }
    }

    private function createTab($className, $name, $parent = 0)
    {
        $idTab = (int) Tab::getIdFromClassName($className);
        if ($idTab) {
            return $idTab;
        }
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->module = $this->module->name;
        $tab->id_parent = (int) $parent;
        $tab->active = 1;
        $names = [];
        foreach (Language::getLanguages(false) as $lang) {
            $names[(int) $lang['id_lang']] = $name;
        }
        $tab->name = $names;
        if ($tab->add()) {
            return (int) $tab->id;
        }

        return 0;
    }
}

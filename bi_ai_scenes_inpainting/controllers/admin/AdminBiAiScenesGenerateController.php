<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Generate admin controller - the main workspace for scene staging,
 * inpainting and upscaling. Hosts the batch table, modal, mask drawer,
 * before/after slider, and AJAX endpoints.
 *
 * AJAX actions (POST 'ajax_action'):
 *   - generate          : start a new render
 *   - poll              : poll a pending prediction by id
 *   - cancel            : cancel a pending prediction
 *   - upload_mask       : persist a base64 mask drawn on the canvas
 *   - delete            : delete a render (file + DB row)
 *   - test_credentials  : check Replicate API key
 *   - list              : list existing renders (filterable)
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   AFL-3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBiAiScenesGenerateController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->module->l('Scenes / Inpaint / Upscale', 'admingenerate');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin-generate.js');
    }

    public function postProcess()
    {
        if (Tools::getValue('ajax_action')) {
            $this->ajaxDispatch(Tools::getValue('ajax_action'));
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        $providers = [
            'scene' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_SCENE),
            'inpaint' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_INPAINT),
            'upscale' => BiAiScenesConfiguration::providersForOperation(BiAiScenesApiInterface::OP_UPSCALE),
        ];
        $defaults = [
            'scene' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_SCENE),
            'inpaint' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_INPAINT),
            'upscale' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_PROVIDER_UPSCALE),
            'prompt' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_PROMPT),
            'negative_prompt' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_NEGATIVE_PROMPT),
            'aspect_ratio' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_ASPECT_RATIO),
            'output_format' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_OUTPUT_FORMAT),
            'upscale_factor' => (int) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_DEFAULT_UPSCALE_FACTOR),
        ];
        $this->context->smarty->assign([
            'scenes_providers' => $providers,
            'scenes_defaults' => $defaults,
            'scenes_ajax_url' => $this->context->link->getAdminLink('AdminBiAiScenesGenerate'),
            'scenes_token' => Tools::getAdminTokenLite('AdminBiAiScenesGenerate'),
            'scenes_brush_color' => (string) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_MASK_BRUSH_COLOR),
            'scenes_brush_opacity' => (float) BiAiScenesConfiguration::get(BiAiScenesConfiguration::KEY_MASK_BRUSH_OPACITY),
        ]);
        $this->content = $this->createTemplate('generate.tpl')->fetch();
        parent::initContent();
    }

    /**
     * @param string $action
     */
    private function ajaxDispatch($action)
    {
        header('Content-Type: application/json');
        $service = new BiAiScenesGenerationService($this->module);
        $manager = new BiAiScenesImageManager($this->module);
        $resp = ['success' => false, 'error' => 'Unknown action'];
        try {
            switch ($action) {
                case 'test_credentials':
                    $http = new BiAiScenesHttpClient();
                    $r = $http->get('https://api.replicate.com/v1/account');
                    $resp = $r['success']
                        ? ['success' => true, 'username' => isset($r['data']['username']) ? $r['data']['username'] : '?']
                        : ['success' => false, 'error' => isset($r['error']) ? $r['error'] : 'Unknown'];
                    break;
                case 'generate':
                    $providerKey = (string) Tools::getValue('provider');
                    $params = [
                        'prompt' => (string) Tools::getValue('prompt'),
                        'negative_prompt' => (string) Tools::getValue('negative_prompt'),
                        'image_url' => (string) Tools::getValue('image_url'),
                        'mask_url' => (string) Tools::getValue('mask_url'),
                        'mask_filename' => (string) Tools::getValue('mask_filename'),
                        'aspect_ratio' => (string) Tools::getValue('aspect_ratio'),
                        'output_format' => (string) Tools::getValue('output_format'),
                        'scale' => (int) Tools::getValue('scale'),
                        'seed' => Tools::getValue('seed') !== false && Tools::getValue('seed') !== '' ? (int) Tools::getValue('seed') : null,
                        'num_inference_steps' => (int) Tools::getValue('num_inference_steps'),
                        'guidance_scale' => (float) Tools::getValue('guidance_scale'),
                        'strength' => (float) Tools::getValue('strength'),
                    ];
                    foreach ($params as $k => $v) {
                        if ($v === '' || $v === null) {
                            unset($params[$k]);
                        }
                    }
                    $context = [
                        'id_product' => (int) Tools::getValue('id_product'),
                        'id_product_attribute' => (int) Tools::getValue('id_product_attribute'),
                        'id_image' => (int) Tools::getValue('id_image'),
                    ];
                    $resp = $service->start($providerKey, $params, $context);
                    break;
                case 'poll':
                    $resp = $service->poll((string) Tools::getValue('prediction_id'));
                    break;
                case 'cancel':
                    $ok = $service->cancel((string) Tools::getValue('prediction_id'));
                    $resp = ['success' => (bool) $ok];
                    break;
                case 'upload_mask':
                    $stored = $manager->storeMaskBase64((string) Tools::getValue('data'));
                    if ($stored) {
                        $resp = ['success' => true, 'url' => $stored['url'], 'filename' => $stored['filename']];
                    } else {
                        $resp = ['success' => false, 'error' => 'Failed to save mask'];
                    }
                    break;
                case 'delete':
                    $id = (int) Tools::getValue('id_render');
                    $render = new BiAiScenesRender($id);
                    if (!Validate::isLoadedObject($render)) {
                        $resp = ['success' => false, 'error' => 'Not found'];
                        break;
                    }
                    $manager->delete($render);
                    $render->delete();
                    $resp = ['success' => true];
                    break;
                case 'list':
                    $op = (string) Tools::getValue('operation');
                    $sql = new DbQuery();
                    $sql->select('*')
                        ->from('bi_ai_scenes_renders')
                        ->where('id_shop = ' . (int) $this->context->shop->id)
                        ->orderBy('date_add DESC')
                        ->limit(100);
                    if ($op !== '') {
                        $sql->where("operation = '" . pSQL($op) . "'");
                    }
                    $rows = Db::getInstance()->executeS($sql) ?: [];
                    foreach ($rows as &$r) {
                        $r['image_url'] = !empty($r['image_filename'])
                            ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $r['image_filename'])
                            : '';
                    }
                    $resp = ['success' => true, 'rows' => $rows];
                    break;
                case 'list_products':
                    $resp = $this->ajaxListProducts();
                    break;
                case 'product_images':
                    $resp = $this->ajaxProductImages((int) Tools::getValue('id_product'));
                    break;
                case 'queue_batch':
                    $resp = $this->ajaxQueueBatch();
                    break;
                case 'process_batch_item':
                    $resp = $this->ajaxProcessNextBatchItem($service);
                    break;
                case 'batch_status':
                    $resp = $this->ajaxBatchStatus();
                    break;
            }
        } catch (Exception $e) {
            $resp = ['success' => false, 'error' => $e->getMessage()];
        }
        echo json_encode($resp);
        exit;
    }

    /**
     * Paginated product listing for the batch table. Includes the latest
     * render status per product so we can show a "succeeded / failed /
     * pending" badge inline.
     *
     * @return array
     */
    private function ajaxListProducts()
    {
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $page = max(1, (int) Tools::getValue('page', 1));
        $perPage = max(5, min(100, (int) Tools::getValue('per_page', 20)));
        $search = trim((string) Tools::getValue('search'));
        $statusFilter = (string) Tools::getValue('status');
        $offset = ($page - 1) * $perPage;

        $where = 'p.id_product > 0';
        if ($search !== '') {
            $term = pSQL($search);
            $where .= " AND (pl.name LIKE '%" . $term . "%' OR p.reference LIKE '%" . $term . "%' OR p.id_product = " . (int) $search . ')';
        }

        $countSql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product p'
            . ' INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = ' . $idLang
            . ' WHERE ' . $where;
        $total = (int) Db::getInstance()->getValue($countSql);

        $sql = 'SELECT p.id_product, p.reference, p.active, pl.name'
            . ' FROM ' . _DB_PREFIX_ . 'product p'
            . ' INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = ' . $idLang
            . ' WHERE ' . $where
            . ' ORDER BY p.id_product DESC LIMIT ' . (int) $offset . ', ' . (int) $perPage;
        $rows = Db::getInstance()->executeS($sql) ?: [];

        if (empty($rows)) {
            return ['success' => true, 'rows' => [], 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        }

        $ids = array_map(static function ($r) { return (int) $r['id_product']; }, $rows);
        $idsCsv = implode(',', $ids) ?: '0';

        $latestByProduct = [];
        $latestSql = 'SELECT r.* FROM ' . _DB_PREFIX_ . 'bi_ai_scenes_renders r'
            . ' INNER JOIN (SELECT id_product, MAX(date_upd) AS m FROM ' . _DB_PREFIX_ . 'bi_ai_scenes_renders'
            . ' WHERE id_shop = ' . $idShop . ' AND id_product IN (' . $idsCsv . ') GROUP BY id_product) g'
            . ' ON g.id_product = r.id_product AND g.m = r.date_upd'
            . ' WHERE r.id_shop = ' . $idShop;
        foreach ((Db::getInstance()->executeS($latestSql) ?: []) as $r) {
            $latestByProduct[(int) $r['id_product']] = $r;
        }

        $coverByProduct = [];
        $coverSql = 'SELECT id_product, MIN(id_image) AS id_image FROM ' . _DB_PREFIX_ . 'image'
            . ' WHERE id_product IN (' . $idsCsv . ') AND cover = 1 GROUP BY id_product';
        foreach ((Db::getInstance()->executeS($coverSql) ?: []) as $r) {
            $coverByProduct[(int) $r['id_product']] = (int) $r['id_image'];
        }

        $manager = new BiAiScenesImageManager($this->module);
        foreach ($rows as &$row) {
            $idP = (int) $row['id_product'];
            $row['cover_id_image'] = isset($coverByProduct[$idP]) ? (int) $coverByProduct[$idP] : 0;
            $row['cover_url'] = $row['cover_id_image']
                ? Context::getContext()->link->getImageLink($row['name'], $idP . '-' . $row['cover_id_image'], 'small_default')
                : '';
            $row['has_combinations'] = (bool) Db::getInstance()->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product = ' . $idP . ' LIMIT 1'
            );
            $latest = isset($latestByProduct[$idP]) ? $latestByProduct[$idP] : null;
            if ($latest) {
                $row['render_status'] = $latest['status'];
                $row['render_operation'] = $latest['operation'];
                $row['render_provider'] = $latest['provider_key'];
                $row['render_url'] = !empty($latest['image_filename'])
                    ? $manager->getPublicUrl(BiAiScenesImageManager::SUBDIR_RENDERS, $latest['image_filename'])
                    : '';
                $row['render_date'] = $latest['date_upd'];
            } else {
                $row['render_status'] = '';
                $row['render_url'] = '';
            }
        }
        unset($row);

        if ($statusFilter !== '') {
            $rows = array_values(array_filter($rows, static function ($r) use ($statusFilter) {
                if ($statusFilter === 'none') {
                    return $r['render_status'] === '';
                }
                return $r['render_status'] === $statusFilter;
            }));
        }

        return ['success' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    /**
     * List images + combinations of a single product so the user can pick
     * the source image for a scene/inpaint/upscale operation.
     *
     * @param int $idProduct
     *
     * @return array
     */
    private function ajaxProductImages($idProduct)
    {
        if ($idProduct <= 0) {
            return ['success' => false, 'error' => 'id_product required'];
        }
        $idLang = (int) $this->context->language->id;
        $product = new Product($idProduct, false, $idLang);
        if (!Validate::isLoadedObject($product)) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        $images = [];
        foreach (Image::getImages($idLang, $idProduct) as $img) {
            $images[] = [
                'id_image' => (int) $img['id_image'],
                'cover' => (int) $img['cover'],
                'position' => (int) $img['position'],
                'url' => $this->context->link->getImageLink($product->link_rewrite, $idProduct . '-' . (int) $img['id_image'], 'large_default'),
                'thumb' => $this->context->link->getImageLink($product->link_rewrite, $idProduct . '-' . (int) $img['id_image'], 'small_default'),
            ];
        }

        $combinations = [];
        $combos = $product->getAttributeCombinations($idLang) ?: [];
        $byPa = [];
        foreach ($combos as $c) {
            $idPa = (int) $c['id_product_attribute'];
            if (!isset($byPa[$idPa])) {
                $byPa[$idPa] = [
                    'id_product_attribute' => $idPa,
                    'reference' => (string) $c['reference'],
                    'attributes' => [],
                    'id_image' => 0,
                ];
            }
            $byPa[$idPa]['attributes'][] = $c['group_name'] . ': ' . $c['attribute_name'];
        }
        // Combination → cover image (id_image)
        if ($byPa) {
            $idsPa = implode(',', array_map('intval', array_keys($byPa))) ?: '0';
            $rows = Db::getInstance()->executeS(
                'SELECT id_product_attribute, id_image FROM ' . _DB_PREFIX_ . 'product_attribute_image'
                . ' WHERE id_product_attribute IN (' . $idsPa . ')'
            ) ?: [];
            foreach ($rows as $r) {
                $idPa = (int) $r['id_product_attribute'];
                if (isset($byPa[$idPa]) && empty($byPa[$idPa]['id_image'])) {
                    $byPa[$idPa]['id_image'] = (int) $r['id_image'];
                    $byPa[$idPa]['thumb'] = $this->context->link->getImageLink(
                        $product->link_rewrite,
                        $idProduct . '-' . (int) $r['id_image'],
                        'small_default'
                    );
                }
            }
        }
        $combinations = array_values($byPa);

        return [
            'success' => true,
            'id_product' => $idProduct,
            'name' => (string) $product->name,
            'reference' => (string) $product->reference,
            'images' => $images,
            'combinations' => $combinations,
        ];
    }

    /**
     * Enqueue a batch of (product, image, optional combination) tuples for
     * the requested operation. Items are processed by polling
     * `process_batch_item` from the JS.
     *
     * @return array
     */
    private function ajaxQueueBatch()
    {
        $itemsJson = (string) Tools::getValue('items');
        $items = json_decode($itemsJson, true);
        if (!is_array($items) || !$items) {
            return ['success' => false, 'error' => 'No items'];
        }
        $operation = (string) Tools::getValue('operation');
        $providerKey = (string) Tools::getValue('provider');
        $prompt = (string) Tools::getValue('prompt');
        $params = [
            'negative_prompt' => (string) Tools::getValue('negative_prompt'),
            'aspect_ratio' => (string) Tools::getValue('aspect_ratio'),
            'output_format' => (string) Tools::getValue('output_format'),
            'scale' => (int) Tools::getValue('scale'),
            'strength' => (float) Tools::getValue('strength'),
            'guidance_scale' => (float) Tools::getValue('guidance_scale'),
            'num_inference_steps' => (int) Tools::getValue('num_inference_steps'),
        ];
        $idShop = (int) $this->context->shop->id;
        $idEmployee = (int) (Context::getContext()->employee ? Context::getContext()->employee->id : 0);
        $batchName = 'batch_' . date('Ymd_His') . '_' . substr(md5(microtime(true)), 0, 6);

        $queued = 0;
        foreach ($items as $it) {
            $idProduct = (int) (isset($it['id_product']) ? $it['id_product'] : 0);
            $idPa = (int) (isset($it['id_product_attribute']) ? $it['id_product_attribute'] : 0);
            $imageUrl = (string) (isset($it['image_url']) ? $it['image_url'] : '');
            if ($imageUrl === '' || $idProduct <= 0) {
                continue;
            }
            Db::getInstance()->insert('bi_ai_scenes_batch_queue', [
                'batch_name' => pSQL($batchName),
                'operation' => pSQL($operation),
                'provider_key' => pSQL($providerKey),
                'id_product' => $idProduct,
                'id_product_attribute' => $idPa,
                'image_urls' => pSQL($imageUrl, true),
                'mask_url' => '',
                'prompt' => pSQL($prompt, true),
                'params_json' => pSQL(json_encode($params), true),
                'status' => 'queued',
                'priority' => 0,
                'id_employee' => $idEmployee,
                'id_shop' => $idShop,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]);
            ++$queued;
        }

        return ['success' => true, 'queued' => $queued, 'batch_name' => $batchName];
    }

    /**
     * Pop the next queued item, hand it to the GenerationService and mark
     * the queue row accordingly. Called repeatedly by the JS.
     *
     * @param BiAiScenesGenerationService $service
     *
     * @return array
     */
    private function ajaxProcessNextBatchItem(BiAiScenesGenerationService $service)
    {
        $idShop = (int) $this->context->shop->id;
        $row = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'bi_ai_scenes_batch_queue'
            . " WHERE status = 'queued' AND id_shop = " . $idShop
            . ' ORDER BY priority DESC, id_batch ASC LIMIT 1'
        );
        if (!$row) {
            return ['success' => true, 'done' => true];
        }
        $idBatch = (int) $row['id_batch'];
        Db::getInstance()->update('bi_ai_scenes_batch_queue', [
            'status' => 'processing',
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_batch = ' . $idBatch);

        $params = json_decode((string) $row['params_json'], true) ?: [];
        $params['prompt'] = (string) $row['prompt'];
        $params['image_url'] = (string) $row['image_urls'];
        $context = [
            'id_product' => (int) $row['id_product'],
            'id_product_attribute' => (int) $row['id_product_attribute'],
        ];
        $result = $service->start((string) $row['provider_key'], $params, $context);

        $newStatus = !empty($result['success']) ? 'completed' : (!empty($result['pending']) ? 'processing' : 'failed');
        Db::getInstance()->update('bi_ai_scenes_batch_queue', [
            'status' => $newStatus,
            'error_message' => isset($result['error']) ? pSQL((string) $result['error'], true) : '',
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_batch = ' . $idBatch);

        return [
            'success' => true,
            'id_batch' => $idBatch,
            'item_status' => $newStatus,
            'item_result' => $result,
        ];
    }

    /**
     * Aggregate counts for the batch progress bar.
     *
     * @return array
     */
    private function ajaxBatchStatus()
    {
        $idShop = (int) $this->context->shop->id;
        $rows = Db::getInstance()->executeS(
            'SELECT status, COUNT(*) AS cnt FROM ' . _DB_PREFIX_ . 'bi_ai_scenes_batch_queue'
            . ' WHERE id_shop = ' . $idShop
            . ' GROUP BY status'
        ) ?: [];
        $counts = ['queued' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'canceled' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['cnt'];
        }

        return ['success' => true, 'counts' => $counts];
    }
}

<?php
/**
 * BI - AI Scenes / Inpainting / Upscale.
 * Render ObjectModel - one row per generated image (scene / inpaint / upscale).
 *
 * @author    Anthony Fantinati - Blazing Ideas
 * @copyright 2024-2026 Blazing Ideas - www.fantinati.com
 * @license   Proprietary
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BiAiScenesRender extends ObjectModel
{
    public const OP_SCENE = 'scene';
    public const OP_INPAINT = 'inpaint';
    public const OP_UPSCALE = 'upscale';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    public $id_bi_ai_scenes_render;
    public $id_product;
    public $id_product_attribute;
    public $id_image;
    public $operation;
    public $provider_key;
    public $prompt;
    public $negative_prompt;
    public $mask_filename;
    public $source_image_url;
    public $image_filename;
    public $image_path;
    public $image_width;
    public $image_height;
    public $prediction_id;
    public $status;
    public $error_message;
    public $file_size;
    public $params_json;
    public $id_shop;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'bi_ai_scenes_renders',
        'primary' => 'id_bi_ai_scenes_render',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_image' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'operation' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20, 'required' => true],
            'provider_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'required' => true],
            'prompt' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 4096],
            'negative_prompt' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2048],
            'mask_filename' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'source_image_url' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2048],
            'image_filename' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'image_path' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 512],
            'image_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'image_height' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'prediction_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 20, 'required' => true],
            'error_message' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 2048],
            'file_size' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'params_json' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Find latest succeeded render for a product (+ optional combination).
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int|null $idShop
     *
     * @return BiAiScenesRender|null
     */
    public static function findLatestForProduct($idProduct, $idProductAttribute = 0, $idShop = null)
    {
        $idShop = $idShop ?: (int) Context::getContext()->shop->id;
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('id_product = ' . (int) $idProduct)
            ->where('id_product_attribute = ' . (int) $idProductAttribute)
            ->where('id_shop = ' . (int) $idShop)
            ->where("status = '" . pSQL(self::STATUS_SUCCEEDED) . "'")
            ->orderBy('date_upd DESC')
            ->limit(1);
        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }
        $obj = new self();
        $obj->hydrate($row);

        return $obj;
    }

    /**
     * List renders by product, optionally filtered by operation.
     *
     * @param int $idProduct
     * @param string|null $operation
     *
     * @return array
     */
    public static function listForProduct($idProduct, $operation = null)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('id_product = ' . (int) $idProduct);
        if ($operation !== null) {
            $sql->where("operation = '" . pSQL($operation) . "'");
        }
        $sql->orderBy('date_add DESC');

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Lookup render by Replicate prediction id.
     *
     * @param string $predictionId
     *
     * @return BiAiScenesRender|null
     */
    public static function findByPredictionId($predictionId)
    {
        $sql = new DbQuery();
        $sql->select('id_bi_ai_scenes_render')
            ->from(self::$definition['table'])
            ->where("prediction_id = '" . pSQL($predictionId) . "'")
            ->orderBy('id_bi_ai_scenes_render DESC')
            ->limit(1);
        $id = (int) Db::getInstance()->getValue($sql);
        if (!$id) {
            return null;
        }

        return new self($id);
    }

    /**
     * Aggregate counts by status for the dashboard.
     *
     * @param int|null $idShop
     *
     * @return array<string,int>
     */
    public static function countsByStatus($idShop = null)
    {
        $idShop = $idShop ?: (int) Context::getContext()->shop->id;
        $sql = new DbQuery();
        $sql->select('status, COUNT(*) AS cnt')
            ->from(self::$definition['table'])
            ->where('id_shop = ' . (int) $idShop)
            ->groupBy('status');
        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [
            self::STATUS_PENDING => 0,
            self::STATUS_PROCESSING => 0,
            self::STATUS_SUCCEEDED => 0,
            self::STATUS_FAILED => 0,
            self::STATUS_CANCELED => 0,
        ];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['cnt'];
        }

        return $out;
    }
}

<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Until;

use BlueMedia\OnlinePayments\Gateway;
use Currency;
use Context;
use DbQuery;
use Db;
use Shop;
use HelperList;
use AdminController;
use Tools;
use Module;
use Configuration as Cfg;

class Helper
{
    public static function getFields(): array
    {
        return [
            'BLUEPAYMENT_STATUS_WAIT_PAY_ID',
            'BLUEPAYMENT_STATUS_ACCEPT_PAY_ID',
            'BLUEPAYMENT_STATUS_ERROR_PAY_ID',
            'BLUEPAYMENT_SHOW_PAYWAY',
            'BLUEPAYMENT_TEST_ENV',

            'BLUEPAYMENT_GA_TYPE',
            'BLUEPAYMENT_GA_TRACKER_ID',
            'BLUEPAYMENT_GA4_TRACKER_ID',
            'BLUEPAYMENT_GA4_SECRET',

            'BLUEPAYMENT_BLIK_REDIRECT',
            'BLUEPAYMENT_GPAY_REDIRECT',
            'BLUEPAYMENT_PROMO_PAY_LATER',
            'BLUEPAYMENT_PROMO_INSTALMENTS',
            'BLUEPAYMENT_PROMO_MATCHED_INSTALMENTS',
            'BLUEPAYMENT_PROMO_HEADER',
            'BLUEPAYMENT_PROMO_FOOTER',
            'BLUEPAYMENT_PROMO_LISTING',
            'BLUEPAYMENT_PROMO_PRODUCT',
            'BLUEPAYMENT_PROMO_CART',
            'BLUEPAYMENT_PROMO_CHECKOUT',
        ];
    }

    public static function getFieldsLang(): array
    {
        return [
            'BLUEPAYMENT_PAYMENT_NAME',
            'BLUEPAYMENT_PAYMENT_GROUP_NAME',
        ];
    }

    public static function getFieldsService(): array
    {
        return [
            'BLUEPAYMENT_SERVICE_PARTNER_ID',
            'BLUEPAYMENT_SHARED_KEY',
        ];
    }

    public static function getImgPayments($type)
    {
        $currency = Context::getContext()->currency;
        $id_shop = Context::getContext()->shop->id;

        $query = new DbQuery();
        $query->from('blue_gateway_transfers', 'gt');
        $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');

        if ($type === 'transfers') {
            $query->where('gt.gateway_id NOT IN (' . self::getGatewaysList() . ')');
        } elseif ($type === 'wallet') {
            $query->where('gt.gateway_id IN (' . self::getWalletsList() . ')');
        }

        $query->where('gt.gateway_status = 1');
        $query->where('gt.gateway_currency = "' . pSql($currency->iso_code) . '"');

        if (Shop::isFeatureActive()) {
            $query->where('gts.id_shop = ' . (int)$id_shop);
        }

        $query->select('gateway_logo_url, gateway_name');

        return Db::getInstance()->executeS($query);
    }


    public static function getGatewaysList(): string
    {
        $gatewayArray = [
            GATEWAY_ID_BLIK,
            GATEWAY_ID_ALIOR,
            GATEWAY_ID_CARD,
            GATEWAY_ID_GOOGLE_PAY,
            GATEWAY_ID_APPLE_PAY,
            GATEWAY_ID_SMARTNEY
        ];

        return implode(',', $gatewayArray);
    }


    public static function getWalletsList(): string
    {
        $walletsArray = [
            GATEWAY_ID_GOOGLE_PAY,
            GATEWAY_ID_APPLE_PAY
        ];

        return implode(',', $walletsArray);
    }

    public static function parseConfigByCurrency($key, $currencyIsoCode)
    {
        $data = Tools::unSerialize(Cfg::get($key));
        return is_array($data) && array_key_exists($currencyIsoCode, $data) ? $data[$currencyIsoCode] : '';
    }

    /**
     * Get logo
     * @return string
     */
    public static function getBrandLogo(): string
    {
        return Context::getContext()->shop->getBaseURL(true) . 'modules/bluepayment/views/img/blue-media.svg';
    }


    /**
     * @param $id_order
     * @return bool | array
     */
    public static function getLastOrderPaymentByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'blue_transactions
			WHERE order_id like "' . pSQL($id_order) . '-%"
			ORDER BY created_at DESC';

        return Db::getInstance()->getRow($sql, false);
    }


    /**
     * @param $id_order
     * @throws PrestaShopDatabaseException
     * @return bool | array
     */
    public static function getOrdersByOrderId($id_order)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'blue_transactions
			WHERE order_id like "' . pSQL($id_order) . '-%"
			ORDER BY created_at DESC';

        return Db::getInstance()->executeS($sql, true, false);
    }



    /**
     * Generates and returns a hash key based on field values from an array
     * @param array $data
     * @return string
     */
    public static function generateAndReturnHash($data): string
    {
        require_once BM_SDK_PATH;

        $values_array = array_values($data);
        $values_array_filter = array_filter($values_array);

        $comma_separated = implode(',', $values_array_filter);
        $replaced = str_replace(',', HASH_SEPARATOR, $comma_separated);

        return hash(Gateway::HASH_SHA256, $replaced);
    }
}

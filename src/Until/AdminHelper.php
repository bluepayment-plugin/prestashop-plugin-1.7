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

use BluePayment\Until\Helper;
use Currency;
use Context;
use DbQuery;
use Db;
use Shop;
use HelperList;
use AdminController;
use Tools;
use Module;

class AdminHelper
{
    public function renderAdditionalOptionsList($module, $payments, $title)
    {
        $helper = new HelperList();
        $helper->table = 'blue_gateway_channels';
        $helper->name_controller = $module->name;
        $helper->module = $module;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_blue_gateway_channels';
        $helper->no_link = true;
        $helper->title = $title;
        $helper->currentIndex = AdminController::$currentIndex;
        $content = $payments;
        $helper->token = Tools::getAdminTokenLite('AdminBluepaymentPayments');
        $helper->position_identifier = 'position';
        $helper->orderBy = 'position';
        $helper->orderWay = 'ASC';
        $helper->show_toolbar = false;

        return $helper->generateList($content, $this->getGatewaysListFields($module));
    }









    public function displayGatewayLogo($gatewayLogo, $object)
    {

        $name = _MODULE_DIR_ . 'bluepayment/views/img/';

        $currency = $object['gateway_currency'];

        if ($gatewayLogo === $name . 'payments.png') {
            $result = '<div class="bm-slideshow-wrapper">';
            $result .= '<div class="bm-transfers' . $currency . '-slideshow bm-slideshow" data-slideshow="transfers' . $currency . '">';
            foreach (Helper::getImgPayments('transfers') as $row) {
                $result .= '<div class="slide">';
                $result .= '<img src="' . $row['gateway_logo_url'] . '" alt="' . $row['gateway_name'] . '">';
                $result .= '</div>';
            }
            $result .= '</div>';
            $result .= '</div>';
        } elseif ($gatewayLogo === $name . 'cards.png') {
            $result = '<div class="bm-slideshow-wrapper">';
            $result .= '<div class="bm-wallet' . $currency . '-slideshow bm-slideshow" data-slideshow="wallet' . $currency . '">';
            foreach (Helper::getImgPayments('wallet') as $row) {
                $result .= '<div class="slide">';
                $result .= '<img src="' . $row['gateway_logo_url'] . '" alt="' . $row['gateway_name'] . '">';
                $result .= '</div>';
            }
            $result .= '</div>';
            $result .= '</div>';
        } else {
            $result = '<img width="65" class="img-fluid" src="' . $gatewayLogo . '" />';
        }

        return $result;
    }

    public function displayGatewayPayments($gatewayLogo, $object)
    {
        if ($gatewayLogo == 1) {
            return '<div class="btn-info" data-toggle="modal" data-target="#' . str_replace(
                ' ',
                '_',
                $object['gateway_name']
            ) . '_' . $object['gateway_currency'] . '">
            <img class="img-fluid" width="24" src="' . BM_IMAGES_PATH . 'question.png" alt=""></div>';
        } else {
            return '';
        }
    }





    public function getListChannels($currency)
    {
        $id_shop = Context::getContext()->shop->id;

        $query = new DbQuery();
        $query->select('gc.*, gcs.id_shop');
        $query->from('blue_gateway_channels', 'gc');
        $query->leftJoin('blue_gateway_channels_shop', 'gcs', 'gc.id_blue_gateway_channels 
        = gcs.id_blue_gateway_channels');

        $query->where('gc.gateway_currency = "' . pSql($currency) . '"');

        if (Shop::isFeatureActive()) {
            $query->where('gcs.id_shop = ' . (int)$id_shop);
        }

        $query->orderBy('gc.position ASC');
        $query->groupBy('gc.id_blue_gateway_channels');

        return Db::getInstance()->ExecuteS($query);
    }



    private function getGatewaysListFields($module): array
    {
        return [
            'position' => [
                'title' => $module->l('Position'),
                'position' => 'position',
                'ajax' => true,
                'align' => 'center',
                'orderby' => false,
            ],
            'gateway_logo_url' => [
                'title' => $module->l('Payment method'),
                'callback' => 'displayGatewayLogo',
                'callback_object' => self::class,
                'orderby' => false,
                'search' => false,
            ],
            'gateway_name' => [
                'title' => '',
                'orderby' => false,
            ],
            'gateway_payments' => [
                'title' => '',
                'callback' => 'displayGatewayPayments',
                'callback_object' => self::class,
                'orderby' => false,
            ],
        ];
    }




    public function getListAllPayments($currency = 'PLN', $type = null)
    {

        $id_shop = Context::getContext()->shop->id;

        $q = '';
        if ($type === 'wallet') {
            $q = 'IN (' . Helper::getWalletsList() . ')';
        } elseif ($type === 'transfer') {
            $q = 'NOT IN (' . Helper::getGatewaysList() . ')';
        }

        $query = new DbQuery();
        $query->select('gt.*');
        $query->from('blue_gateway_transfers', 'gt');
        $query->leftJoin('blue_gateway_transfers_shop', 'gcs', 'gcs.id = gt.id');
        $query->where('gt.gateway_name ' . $q);
        $query->where('gt.gateway_currency = "' . pSql($currency) . '"');

        if (Shop::isFeatureActive()) {
            $query->where('gcs.id_shop = ' . (int)$id_shop);
        }

        $query->orderBy('gt.position ASC');
        $query->groupBy('gt.id');

        return Db::getInstance()->ExecuteS($query);
    }



    /**
     * Sort currency by id
     * @return array
     */
    public static function getSortCurrencies(): array
    {
        $sortCurrencies = Currency::getCurrenciesByIdShop(Context::getContext()->shop->id);

        usort($sortCurrencies, function ($a, $b) {
            if ($a['id_currency'] == $b['id_currency']) {
                return 0;
            }
            return $a['id_currency'] > $b['id_currency'] ? 1 : -1;
        });
        return (array)$sortCurrencies;
    }
}

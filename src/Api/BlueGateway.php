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

namespace BluePayment\Api;

use Module;
use BluePayment\BlueAPI;

class BlueGateway
{
    private $module; // do usuniecia prawdopodobnie
    private $api;

    public function __construct(\BluePayment $module, $api)
    {
        $this->api = $api;
        $this->module = $module;
    }

    public function getTransfers()
    {
        $this->api->getGatewaysFromAPI(new BlueGatewayTransfers());
    }

    public function getChannels()
    {
        $this->api->getGatewaysFromAPI(new BlueGatewayChannels());
    }

    public function clearGateway()
    {
        $id_shop = \Context::getContext()->shop->id;

        try {
            $sql = 'DELETE gt.*, gs.*
                FROM ' . _DB_PREFIX_ . 'blue_gateway_transfers gt
                INNER JOIN ' . _DB_PREFIX_ . 'blue_gateway_transfers_shop gs
                ON (gs.id = gt.id)';

            if (\Shop::isFeatureActive()) {
                $sql .= 'WHERE gs.id_shop = ' . (int)$id_shop;
            }

            \Db::getInstance()->execute($sql);
            \PrestaShopLogger::addLog('BM - Clear gateway transfers', 1);
        } catch (Exception $exception) {
            \PrestaShopLogger::addLog('BM - Error clear gateway transfers', 1);
        }

        try {
            $sql = 'DELETE gtt.*, gss.*
                FROM `' . _DB_PREFIX_ . 'blue_gateway_channels` gtt
                INNER JOIN `' . _DB_PREFIX_ . 'blue_gateway_channels_shop` gss
                ON (gss.id_blue_gateway_channels = gtt.id_blue_gateway_channels)';

            if (\Shop::isFeatureActive()) {
                $sql .= 'WHERE gss.id_shop = ' . (int)$id_shop;
            }

            \Db::getInstance()->execute($sql);

            \PrestaShopLogger::addLog('BM - Clear gateway channels', 1);
        } catch (Exception $exception) {
            \PrestaShopLogger::addLog('BM - Error clear gateway channels', 1);
        }
    }
}

<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html.
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Api;

use BlueMedia\OnlinePayments\Gateway;
use BluePayment\Until\AdminHelper;
use BluePayment\Until\Helper;
use Configuration as Cfg;
use Module;

class BlueAPI
{
    private $module;

    public function __construct(\BluePayment $module)
    {
        $this->module = $module;
    }

    private function gatewayAuthentication($serviceId, $hashKey)
    {
        if ($serviceId > 0 && !empty($hashKey)) {
            return $this->connectFromAPI($serviceId, $hashKey);
        }

        return false;
    }

    public function getGatewaysFromAPI($channels)
    {
        $position = 0;

        if (!is_object($channels)) {
            return false;
        }

        foreach (AdminHelper::getSortCurrencies() as $currency) {
            $apiResponse = $this->gatewayAccount($currency['iso_code']);
            if ($apiResponse) {
                $position = (int) $channels->syncGateway($apiResponse, $currency, $position);
            }
        }

        return false;
    }

    public function isConnectedAPI($serviceId, $hashKey): bool
    {
        require_once BM_SDK_PATH;

        $testMode = Cfg::get($this->module->name_upper . '_TEST_ENV');
        $gatewayMode = $testMode ?
            Gateway::MODE_SANDBOX :
            Gateway::MODE_LIVE;

        $gateway = new Gateway(
            $serviceId,
            $hashKey,
            $gatewayMode,
            Gateway::HASH_SHA256,
            HASH_SEPARATOR
        );

        try {
            return (bool) $gateway->doPaywayList();
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function connectFromAPI($serviceId, $hashKey)
    {
        require_once BM_SDK_PATH;

        $testMode = Cfg::get($this->module->name_upper . '_TEST_ENV');
        $gatewayMode = $testMode ?
            Gateway::MODE_SANDBOX :
            Gateway::MODE_LIVE;

        $gateway = new Gateway(
            $serviceId,
            $hashKey,
            $gatewayMode,
            Gateway::HASH_SHA256,
            HASH_SEPARATOR
        );

        try {
            return $gateway->doPaywayList();
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function gatewayAccount($currencyCode)
    {
        $serviceId = Helper::parseConfigByCurrency($this->module->name_upper . '_SERVICE_PARTNER_ID', $currencyCode);
        $hashKey = Helper::parseConfigByCurrency($this->module->name_upper . '_SHARED_KEY', $currencyCode);

        return $this->gatewayAuthentication($serviceId, $hashKey);
    }
}

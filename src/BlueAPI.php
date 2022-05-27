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

if (!defined('_PS_VERSION_')) {
    exit;
}

class BlueAPI
{

    private $module;

    public function __construct()
    {
        $this->module = new BluePayment();
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

        foreach ($this->module->getSortCurrencies() as $currency) {
            $apiResponse = $this->gatewayAccount($currency['iso_code']);
            if ($apiResponse) {
                $position = (int)$channels->syncGateway($apiResponse, $currency, $position);
            }
        }

        return false;
    }

    private function connectFromAPI($serviceId, $hashKey)
    {
        require_once dirname(__FILE__).'/../sdk/index.php';

        $test_mode = Configuration::get($this->module->name_upper.'_TEST_ENV');
        $gateway_mode = $test_mode ?
            \BlueMedia\OnlinePayments\Gateway::MODE_SANDBOX :
            \BlueMedia\OnlinePayments\Gateway::MODE_LIVE;

        $gateway = new \BlueMedia\OnlinePayments\Gateway(
            $serviceId,
            $hashKey,
            $gateway_mode,
            \BlueMedia\OnlinePayments\Gateway::HASH_SHA256,
            HASH_SEPARATOR
        );

        try {
            return $gateway->doPaywayList();
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function gatewayAccount($currency_code)
    {
        $serviceId = $this->module->parseConfigByCurrency(
            $this->module->name_upper.'_SERVICE_PARTNER_ID',
            $currency_code
        );
        $hashKey = $this->module->parseConfigByCurrency(
            $this->module->name_upper.'_SHARED_KEY',
            $currency_code
        );

        return $this->gatewayAuthentication($serviceId, $hashKey);
    }
}

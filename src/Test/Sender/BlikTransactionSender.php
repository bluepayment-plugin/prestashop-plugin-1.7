<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Test\Sender;

use BlueMedia\OnlinePayments\Gateway;
use BluePayment\Config\Config;
use BluePayment\Until\Helper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sender for BLIK transaction requests to payment gateway
 */
final class BlikTransactionSender
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param \Module $module Module instance
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Send request to BlueMedia gateway
     *
     * @param string $serviceId Service ID
     * @param string $sharedKey Shared key
     * @param string $orderId Order ID
     * @param string $amount Amount
     * @param string $currency Currency
     * @param string $customerEmail Customer email
     * @param string $blikCode BLIK code
     *
     * @return \SimpleXMLElement|false Response or false on failure
     */
    public function sendRequest(
        string $serviceId,
        string $sharedKey,
        string $orderId,
        string $amount,
        string $currency,
        string $customerEmail,
        string $blikCode
    ) {
        $test_mode = \Configuration::get('BLUEPAYMENT_TEST_ENV');
        $gateway_mode = $test_mode
            ? Gateway::MODE_SANDBOX
            : Gateway::MODE_LIVE;

        $gateway = new Gateway((int) $serviceId, $sharedKey, $gateway_mode);

        $data = [
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Description' => 'BLIK Payment',
            'GatewayID' => (string) Config::GATEWAY_ID_BLIK,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'CustomerIP' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'Title' => 'BLIK Payment',
            'AuthorizationCode' => $blikCode,
            'ScreenType' => 'FULL',
            'PlatformName' => 'PrestaShop',
            'PlatformVersion' => \defined('_PS_VERSION_') ? _PS_VERSION_ : '1.7',
            'PlatformPluginVersion' => $this->module->version,
        ];

        $hash = array_merge($data, [$sharedKey]);
        $hash = Helper::generateAndReturnHash($hash);

        $data['Hash'] = $hash;
        $fields = http_build_query($data);

        try {
            $curl = curl_init($gateway::getActionUrl($gateway::PAYMENT_ACTON_PAYMENT));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['BmHeader: pay-bm-continue-transaction-url']);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $curlResponse = curl_exec($curl);
            curl_close($curl);

            if ($curlResponse === 'ERROR') {
                return false;
            }

            return simplexml_load_string($curlResponse);
        } catch (\Exception $e) {
            \Tools::error_log($e);

            return false;
        }
    }
}

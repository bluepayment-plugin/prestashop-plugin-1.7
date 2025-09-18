<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html.
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BluePayment\Config\Config;
use BluePayment\Until\Helper;

class Refund
{
    private $module;

    public function __construct(\BluePayment $module)
    {
        $this->module = $module;
    }

    public function refundOrder(int $orderId, $amount, $remoteId, \Currency $currency): array
    {
        $amount = number_format((float) $amount, 2, '.', '');

        $serviceId = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SERVICE_PARTNER_ID,
            $currency->iso_code
        );
        $sharedKey = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SHARED_KEY,
            $currency->iso_code
        );
        $messageId = $this->randomString(32);
        // Hash generate from array
        $hashData = [$serviceId, $messageId, $remoteId, $amount, $currency->iso_code, $sharedKey];
        // Hash key
        $hashConfirmation = Helper::generateAndReturnHash($hashData);

        $curl = curl_init();
        $postfields = 'ServiceID=' . $serviceId
            . '&MessageID=' . $messageId
            . '&RemoteID=' . $remoteId
            . '&Amount=' . $amount
            . '&Currency=' . $currency->iso_code
            . '&Hash=' . $hashConfirmation;
        $test_mode = \Configuration::get($this->module->name_upper . '_TEST_ENV');
        $payUrl = $test_mode ? \BlueMedia\OnlinePayments\Gateway::PAYMENT_DOMAIN_SANDBOX : \BlueMedia\OnlinePayments\Gateway::PAYMENT_DOMAIN_LIVE;
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://' . $payUrl . '/settlementapi/transactionRefund',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $resultSuccess = false;

        if (isset($xml->messageID) && (string) $xml->messageID == $messageId) {
            try {
                $db = \Db::getInstance();
                $data = [
                    'order_id' => (int) $orderId,
                    'remote_id' => pSQL($remoteId),
                    'remote_out_id' => null,
                    'amount' => (float) $amount,
                    'currency' => pSQL($currency->iso_code),
                    'message_id' => pSQL($messageId),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($db->insert('blue_gateways_refunds', $data)) {
                    $resultSuccess = true;
                    $info = $this->module->l('Refund request submitted. Waiting for confirmation.', 'refund');
                } else {
                    $info = $this->module->l('Failed to record refund request in database: ', 'refund') . $db->getMsgError();
                    \PrestaShopLogger::addLog(
                        'Autopay Refund Error: Failed to insert into blue_gateways_refunds. DB Error: ' . $db->getMsgError() .
                        ' Data: ' . print_r($data, true),
                        3
                    );
                }
            } catch (\PrestaShopDatabaseException $e) {
                $resultSuccess = false;
                $info = $this->module->l('Database error while recording refund request.', 'refund');
                \PrestaShopLogger::addLog(
                    'Autopay Refund Error: Exception during insert into blue_gateways_refunds. Exception: ' . $e->getMessage() .
                    ' Data: ' . print_r($data, true),
                    3
                );
            }
        } elseif (isset($xml->description)) {
            $info = (string) $xml->description;
        } elseif (curl_errno($curl)) {
            $info = $this->module->l('Curl error: ', 'refund') . curl_error($curl);
        } else {
            $info = $this->module->l('Unknown error during refund request or invalid response structure.', 'refund');
            \PrestaShopLogger::addLog(
                'Autopay Refund Error: Unknown error or invalid response. Response: ' . $response,
                3
            );
        }

        return [$resultSuccess, $info];
    }

    public function randomString($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[rand(0, \Tools::strlen($characters) - 1)];
        }

        return $randomString;
    }
}

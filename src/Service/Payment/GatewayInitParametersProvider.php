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

namespace BluePayment\Service\Payment;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GatewayInitParametersProvider
{
    public function forGateway(int $gatewayId, string $currencyIso, \Cart $cart, int $shopId): array
    {
        try {
            $channelRow = $this->getChannelData($gatewayId, $currencyIso, $shopId);
            $customerData = $this->extractCustomerData($cart);

            return $this->buildExtraParams($channelRow, $customerData);
        } catch (\Exception $e) {
            \Tools::error_log($e);

            return [];
        }
    }

    private function getChannelData(int $gatewayId, string $currencyIso, int $shopId): ?array
    {
        $q = new \DbQuery();
        $q->select('gc.available_for, gc.required_params');
        $q->from('blue_gateway_channels', 'gc');
        $q->leftJoin('blue_gateway_channels_shop', 'gcs', 'gcs.id_blue_gateway_channels = gc.id_blue_gateway_channels');
        $q->where('gc.gateway_id = ' . (int) $gatewayId);
        $q->where('gc.gateway_currency = "' . pSQL($currencyIso) . '"');
        $q->where('gc.gateway_status = 1');
        if (\Shop::isFeatureActive()) {
            $q->where('gcs.id_shop = ' . (int) $shopId);
        }

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($q);

        return $result ?: null;
    }

    private function extractCustomerData(\Cart $cart): array
    {
        $data = [
            'nip' => '',
            'accountHolderName' => '',
            'customerEmail' => '',
        ];

        $invoiceAddress = new \Address((int) $cart->id_address_invoice);
        if (\Validate::isLoadedObject($invoiceAddress)) {
            if (!empty($invoiceAddress->vat_number)) {
                $data['nip'] = trim($invoiceAddress->vat_number);
            }

            $candidateName = trim(trim((string) $invoiceAddress->firstname) . ' ' . trim((string) $invoiceAddress->lastname));
            if ($candidateName !== '') {
                $data['accountHolderName'] = $candidateName;
            }
        }

        if ($data['nip'] === '' || $data['accountHolderName'] === '') {
            $deliveryAddress = new \Address((int) $cart->id_address_delivery);
            if (\Validate::isLoadedObject($deliveryAddress)) {
                if ($data['nip'] === '' && !empty($deliveryAddress->vat_number)) {
                    $data['nip'] = trim($deliveryAddress->vat_number);
                }

                if ($data['accountHolderName'] === '') {
                    $candidateName = trim(trim((string) $deliveryAddress->firstname) . ' ' . trim((string) $deliveryAddress->lastname));
                    if ($candidateName !== '') {
                        $data['accountHolderName'] = $candidateName;
                    }
                }
            }
        }

        $customer = new \Customer((int) $cart->id_customer);
        if (\Validate::isLoadedObject($customer) && !empty($customer->email)) {
            $data['customerEmail'] = trim($customer->email);
        }

        return $data;
    }

    private function buildExtraParams(?array $channelRow, array $customerData): array
    {
        $extra = [];

        if (empty($channelRow)) {
            return $extra;
        }

        if ($this->isB2BChannel($channelRow) && !empty($customerData['nip'])) {
            $extra['Nip'] = $customerData['nip'];
        }

        $requiredParams = $this->parseRequiredParams($channelRow);
        foreach ($requiredParams as $param) {
            if (strcasecmp($param, 'AccountHolderName') === 0 && !empty($customerData['accountHolderName'])) {
                $extra['AccountHolderName'] = $customerData['accountHolderName'];
            }
            if (strcasecmp($param, 'customerEmail') === 0 && !empty($customerData['customerEmail'])) {
                $extra['CustomerEmail'] = $customerData['customerEmail'];
            }
        }

        return $extra;
    }

    private function isB2BChannel(array $channelRow): bool
    {
        return isset($channelRow['available_for']) && strtoupper($channelRow['available_for']) === 'B2B';
    }

    private function parseRequiredParams(array $channelRow): array
    {
        if (empty($channelRow['required_params'])) {
            return [];
        }

        $required = json_decode((string) $channelRow['required_params'], true);

        return is_array($required) ? array_map('strval', $required) : [];
    }
}

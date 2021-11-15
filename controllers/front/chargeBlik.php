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

/**
 * @property BluePayment $module
 */
class BluePaymentChargeBlikModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();

        $status = true;
        $blikCode = pSQL(Tools::getValue('blikCode'));
        $postOrderId = pSQL(Tools::getValue('postOrderId'));

        if (preg_match('/[^a-z_\-0-9 ]/i', $blikCode) && Tools::strlen($blikCode) !== BLIK_CODE_LENGTH) {
            $status = false;
        }

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            $cart = $this->context->cart;
        } else {
            if (empty($postOrderId)) {
                $cart = $this->context->cart;
            } else {
                // https://www.prestashop.com/forums/topic/598871-restore-cart-with-order-id-or-order-details/
                // https://stackoverflow.com/questions/42735643/want-to-restore-the-cart-with-the-order-id-and-details-in-prestashop
                $orderIdItem = explode('-', $postOrderId);
                $orderIdItem = empty($orderIdItem[0]) ? 0 : $orderIdItem[0];
                $cart = Cart::getCartByOrderId($orderIdItem);
            }
        }

        if ($cart->id_customer === 0 ||
            $cart->id_address_delivery === 0 ||
            $cart->id_address_invoice === 0 ||
            !$this->module->active
        ) {
            $status = false;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $status = false;
        }

        if (!$status) {
            echo json_encode([
                'status'  => 'FAILURE',
                'message' => $this->module->l('Client identificator not provided.', 'chargeblik'),
            ]);
            exit;
        }

        $currency = $this->context->currency->iso_code;

        $serviceId = $this->module->parseConfigByCurrency($this->module->name_upper . '_SERVICE_PARTNER_ID', $currency);
        $sharedKey = $this->module->parseConfigByCurrency($this->module->name_upper . '_SHARED_KEY', $currency);

        $totalPaid = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $amount = number_format(round($totalPaid, 2), 2, '.', '');

        $customer = new Customer($cart->id_customer);
        $customerEmail = $customer->email;

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            $this->moduleValidateOrder($cart->id, $amount, $customer);
        }

        $orderId = $this->module->currentOrder . '-' . time();

        if (!empty($postOrderId)) {
            $orderId = $postOrderId;
        }

        $transaction = $this->getTransactionData($orderId, $blikCode);

        if (empty($transaction)) {
            $request = $this->sendRequest(
                $serviceId,
                $sharedKey,
                $orderId,
                $amount,
                $currency,
                $customerEmail,
                $blikCode
            );

            $result = $this->validateRequest($request, $orderId, $blikCode);
        } else {
            $result = $this->validateTransaction($transaction, $orderId);
        }

        $result['postOrderId'] = $orderId;

        if ($result['status'] == 'SUCCESS') {
            $result['backUrl'] = $this->context->link->getModuleLink(
                'bluepayment',
                'blik',
                [
                    'OrderID'       => $orderId,
                    'PaymentStatus' => $result['status'],
                ],
                true
            );
        }

        echo json_encode($result);
        exit;
    }

    private function getTransactionData($orderId, $blikCode)
    {
        $query = new DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = \'' . (int) $orderId . '\'')
            ->where('blik_code = \'' . pSQL($blikCode) . '\'')
            ->select('*');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    private function sendRequest($serviceId, $sharedKey, $orderId, $amount, $currency, $customerEmail, $blikCode)
    {
        require_once dirname(__FILE__) . '/../../sdk/index.php';

        $test_mode = Configuration::get($this->module->name_upper . '_TEST_ENV');
        $gateway_mode = $test_mode ?
            \BlueMedia\OnlinePayments\Gateway::MODE_SANDBOX :
            \BlueMedia\OnlinePayments\Gateway::MODE_LIVE;

        $gateway = new \BlueMedia\OnlinePayments\Gateway($serviceId, $sharedKey, $gateway_mode);

        $data = [
            'ServiceID'         => $serviceId,
            'OrderID'           => $orderId,
            'Amount'            => $amount,
            'Description'       => 'BLIK Payment',
            'GatewayID'         => (string)\BlueMedia\OnlinePayments\Model\Gateway::GATEWAY_ID_BLIK,
            'Currency'          => $currency,
            'CustomerEmail'     => $customerEmail,
            'CustomerIP'        => $_SERVER['REMOTE_ADDR'],
            'Title'             => 'BLIK Payment',
            'AuthorizationCode' => $blikCode,
            'ScreenType'        => 'FULL',
        ];

        $hash = array_merge($data, [$sharedKey]);
        $hash = $this->module->generateAndReturnHash($hash);

        $data['Hash'] = $hash;
        $fields = is_array($data) ? http_build_query($data) : $data;

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
        } catch (Exception $e) {
            Tools::error_log($e);

            return false;
        }
    }

    private function validateRequest($response, $orderId, $blikCode)
    {
        $array = [];
        $data = [
            'order_id'   => (int)$orderId,
            'blik_code'  => pSQL($blikCode),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $query = new DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = \'' . (int) $orderId . '\'')
            ->select('*');

        $transaction = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);

        if (isset($response->confirmation) && $response->confirmation == 'CONFIRMED') {
            if ($response->paymentStatus == 'PENDING') {
                $array = [
                    'status'  => 'PENDING',
                    'message' => $this->module->l('Confirm the operation in your bank\'s application.', 'chargeblik'),
                ];
                $data['blik_status'] = 'PENDING';
                if (empty($transaction)) {
                    Db::getInstance()->insert('blue_transactions', $data);
                } else {
                    unset($data['order_id']);
                    Db::getInstance()->update('blue_transactions', $data, 'order_id = \'' . (int) $orderId . '\'');
                }
            } elseif ($response->paymentStatus == 'SUCCESS') {
                $array = [
                    'status'  => 'SUCCESS',
                    'message' => $this->module->l('Payment has been successfully completed.', 'chargeblik'),
                ];
                $data['blik_status'] = 'SUCCESS';
                if (empty($transaction)) {
                    Db::getInstance()->insert('blue_transactions', $data);
                } else {
                    unset($data['order_id']);
                    Db::getInstance()->update('blue_transactions', $data, 'order_id = \'' . (int) $orderId . '\'');
                }
            } else {
                $array = [
                    'status'  => 'FAILURE',
                    'message' => $this->module->l('The entered code is not valid.', 'chargeblik'),
                ];
            }
        } elseif (isset($response->confirmation) &&
            $response->confirmation == 'NOTCONFIRMED' &&
            $response->reason == 'WRONG_TICKET'
        ) {
            $array = [
                'status'  => 'FAILURE',
                'message' => $this->module->l('The entered code is not valid.', 'chargeblik'),
            ];
            $data['blik_status'] = 'WRONG_TICKET';


            if (empty($transaction)) {
                Db::getInstance()->insert('blue_transactions', $data);
            } else {
                unset($data['order_id']);
                Db::getInstance()->update('blue_transactions', $data, 'order_id = \'' . (int) $orderId . '\'');
            }
        } elseif (isset($response->confirmation) &&
            $response->confirmation == 'NOTCONFIRMED' &&
            $response->reason == 'MULTIPLY_PAID_TRANSACTION'
        ) {
            $array = [
                'status'  => 'FAILURE',
                'message' => $this->module->l('Your BLIK transaction has already been paid for.', 'chargeblik'),
            ];
            $data['blik_status'] = 'MULTIPLY_PAID_TRANSACTION';
            if (empty($transaction)) {
                Db::getInstance()->insert('blue_transactions', $data);
            } else {
                unset($data['order_id']);
                Db::getInstance()->update('blue_transactions', $data, 'order_id = \'' . (int) $orderId . '\'');
            }
        }

        if (empty($array)) {
            $array = [
                'status'  => 'FAILURE',
                'message' => $this->module->l('The code has expired. Try again.', 'chargeblik'),
            ];
        }

        return $array;
    }

    private function validateTransaction($transaction, $orderId)
    {
        $array = [];
        $transaction = (object)$transaction;

        if (isset($transaction->blik_status) && $transaction->blik_status == 'SUCCESS') {
            $array = [
                'status'  => 'SUCCESS',
                'message' => $this->module->l('Payment has been successfully completed.', 'chargeblik'),
            ];
        }
        if (isset($transaction->blik_status) && $transaction->blik_status == 'PENDING') {
            $array = [
                'status'  => 'PENDING',
                'message' => $this->module->l('Confirm the operation in your bank\'s application.', 'chargeblik'),
            ];
        }
        if (isset($transaction->created_at) &&
            time() >= strtotime('+2 minutes', strtotime($transaction->created_at))
        ) {
            $array = [
                'status'  => 'FAILURE',
                'message' => $this->module->l('The BLIK code has expired.', 'chargeblik'),
            ];
            Db::getInstance()->update(
                'blue_transactions',
                ['created_at' => date('Y-m-d H:i:s')],
                'order_id = \'' . (int) $orderId . '\''
            );
        }

        if (empty($array)) {
            $array = [
                'status'  => 'FAILURE',
                'message' => $this->module->l('The code has expired. Try again.', 'chargeblik'),
            ];
        }

        return $array;
    }

    private function moduleValidateOrder($cartId, $amount, $customer)
    {
        $this->module->validateOrder(
            $cartId,
            Configuration::get($this->module->name_upper . '_STATUS_WAIT_PAY_ID', 'bluepayment'),
            $amount,
            $this->module->displayName,
            null,
            [],
            null,
            false,
            $customer->secure_key
        );
    }
}

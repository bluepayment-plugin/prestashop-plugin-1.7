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

use BluePayment\Config\Config;
use BluePayment\Until\Helper;

class BluePaymentChargeSmartneyModuleFrontController extends ModuleFrontController
{

    const FAILURE_DURRING_MSG = 'An error occurred during the transaction.';
    const FAILURE_PAID_MSG = 'Your transaction has already been paid.';
    const FAILURE_EXPIRED = 'Pay Smartney token has expired. Try again.';

    const PAYMENT_SUCCESS = 'Payment has been successfully completed.';
    const PAYMENT_PROGRESS = 'Transaction in progress.';
    const PAYMENT_WAITING = 'We are waiting for payment confirmation';
    const PAYMENT_FAILED = 'Transaction failed.';
    const PAYMENT_EXPIRED = 'The code has expired. Try again.';

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode('Invalid method');
            exit;
        }

        $status = true;
        $postOrderId = pSQL(Tools::getValue('postOrderId'));

        if (Validate::isLoadedObject($this->context->cart) && !$this->context->cart->OrderExists()) {
            $cart = $this->context->cart;
        } else {
            $cart = $this->checkIfEmptyOrderId($postOrderId);
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
                'status' => 'FAILURE',
                'message' => $this->module->l('Client identificator not provided.', 'chargesmartney'),
            ]);
            exit;
        }

        $currency = $this->context->currency->iso_code;

        $serviceId = Helper::parseConfigByCurrency($this->module->name_upper.Config::SERVICE_PARTNER_ID, $currency);
        $sharedKey = Helper::parseConfigByCurrency($this->module->name_upper.Config::SHARED_KEY, $currency);

        $totalPaid = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $amount = number_format(round($totalPaid, 2), 2, '.', '');

        $customer = new Customer($cart->id_customer);
        $customerEmail = $customer->email;

        if (Validate::isLoadedObject($this->context->cart) && !$this->context->cart->OrderExists()) {
            $this->moduleValidateOrder($cart->id, $amount, $customer);
        }

        $orderId = $this->module->currentOrder.'-'.time();

        if (!empty($postOrderId)) {
            $orderId = $postOrderId;
        }

        $token = Tools::getValue('token');
        require_once dirname(__FILE__).'/../../sdk/index.php';

        $transaction = $this->getTransactionData(
            $orderId,
            (string)\BlueMedia\OnlinePayments\Model\Gateway::GATEWAY_ID_SMARTNEY
        );

        if (empty($transaction)) {
            $request = $this->sendRequest($serviceId, $sharedKey, $orderId, $amount, $currency, $customerEmail, $token);
            $result = $this->validateRequest($request, $orderId);
        } else {
            $result = $this->validateTransaction($transaction, $orderId);
        }

        $result['postOrderId'] = $orderId;

        if ($result['status'] == 'SUCCESS') {
            $result['backUrl'] = $this->context->link->getModuleLink(
                'bluepayment',
                'smartney',
                [
                    'OrderID' => $orderId,
                    'PaymentStatus' => $result['status'],
                ],
                true
            );
        }

        echo json_encode($result);
        exit;
    }

    private function sendRequest($serviceId, $sharedKey, $orderId, $amount, $currency, $customerEmail, $token)
    {
        $test_mode = Configuration::get($this->module->name_upper.'_TEST_ENV');
        $gateway_mode = $test_mode ?
            \BlueMedia\OnlinePayments\Gateway::MODE_SANDBOX :
            \BlueMedia\OnlinePayments\Gateway::MODE_LIVE;

        $gateway = new \BlueMedia\OnlinePayments\Gateway($serviceId, $sharedKey, $gateway_mode);

        $data = [
            'ServiceID' => $serviceId,
            'OrderID' => $orderId,
            'Amount' => $amount,
            'Description' => 'Pay Smartney Payment',
            'GatewayID' => (string)\BlueMedia\OnlinePayments\Model\Gateway::GATEWAY_ID_SMARTNEY,
            'Currency' => $currency,
            'CustomerEmail' => $customerEmail,
            'CustomerIP' => $_SERVER['REMOTE_ADDR'],
            'Title' => 'Pay Smartney Payment',
            'PaymentToken' => base64_encode(json_encode($token))
        ];

        $hash = array_merge($data, [$sharedKey]);
        $hash = Helper::generateAndReturnHash($hash);

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

    private function checkIfEmptyOrderId($postOrderId)
    {
        if (empty($postOrderId)) {
            $cart = $this->context->cart;
        } else {
            $orderIdItem = explode('-', $postOrderId);
            $orderIdItem = empty($orderIdItem[0]) ? 0 : $orderIdItem[0];
            $cart = Cart::getCartByOrderId($orderIdItem);

            if (!Validate::isLoadedObject($cart) || (int)$this->context->cart->id != $cart->id) {
                throw new OrderException('Invalid cart provided.');
            }
        }

        return $cart;
    }

    private function validateTransaction($transaction, $orderId)
    :array
    {
        $array = [];
        $transaction = (object)$transaction;

        if (isset($transaction->payment_status) && $transaction->payment_status == 'SUCCESS') {
            $array = [
                'status' => 'SUCCESS',
                'message' => $this->module->l(self::PAYMENT_SUCCESS, 'chargesmartney'),
                'transaction' => $transaction
            ];
        }
        if (isset($transaction->payment_status) && $transaction->payment_status == 'PENDING') {
            $array = [
                'status' => 'PENDING',
                'message' => $this->module->l(self::PAYMENT_WAITING, 'chargesmartney'),
                'transaction' => $transaction
            ];
        }
        if (isset($transaction->created_at) &&
            time() >= strtotime('+7 minutes', strtotime($transaction->created_at))
        ) {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::PAYMENT_FAILED, 'chargesmartney'),
            ];
            Db::getInstance()->update(
                'blue_transactions',
                ['created_at' => date('Y-m-d H:i:s')],
                'order_id = '.(int)$orderId
            );
        }

        if (empty($array)) {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::PAYMENT_EXPIRED, 'chargesmartney'),
            ];
        }

        return $array;
    }

    /**
     * Get transaction by order id
     * @param $orderId
     * @return mixed
     */
    private function getTransactionByOrderId($orderId)
    {
        $query = new DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = '. (int)($orderId))
            ->select('*');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    private function validateRequest($response, $orderId)
    {
        $array = [];
        $data = [
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($response->confirmation)) {
            $array = $this->hasConfirmation($response, $orderId, $data);
        } else {
            $array = $this->emptyConfirmation($response, $orderId);
        }

        if (empty($array)) {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::FAILURE_EXPIRED, 'chargesmartney'),
            ];
        }

        return $array;
    }


    private function emptyConfirmation($response, $orderId)
    :array
    {
        if ($response->status == 'PENDING') {
            $array = [
                'status' => 'PENDING',
                'message' => $this->module->l(self::PAYMENT_PROGRESS, 'chargesmartney'),
            ];

            $data['payment_status'] = 'PENDING';

            $this->transactionQuery($orderId, $data);

            if ($response->redirecturl && isset($response->redirecturl[0])) {
                $redirectUrl = (array)$response->redirecturl;
                $array['redirectUrl'] = $redirectUrl[0] ?? null;
            } elseif ($response->redirecturl) {
                $array['redirectUrl'] = $response->redirecturl;
            }
        } elseif ($response->status == 'SUCCESS') {
            $array = [
                'status' => 'SUCCESS',
                'message' => $this->module->l(self::PAYMENT_SUCCESS, 'chargesmartney'),
            ];
            $data['payment_status'] = 'SUCCESS';

            $this->transactionQuery($orderId, $data);
        } else {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::FAILURE_DURRING_MSG, 'chargesmartney'),
            ];
        }

        return $array;
    }

    private function hasConfirmation($response, $orderId, $data)
    :array
    {
        $array = [];
        if ($response->confirmation == 'NOTCONFIRMED' && $response->reason == 'WRONG_TICKET') {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::FAILURE_DURRING_MSG, 'chargesmartney'),
            ];
            $data['payment_status'] = 'WRONG_TICKET';
            $this->transactionQuery($orderId, $data);
        } elseif ($response->confirmation == 'NOTCONFIRMED' && $response->reason == 'MULTIPLY_PAID_TRANSACTION') {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::FAILURE_PAID_MSG, 'chargesmartney'),
            ];
            $data['payment_status'] = 'MULTIPLY_PAID_TRANSACTION';
            $this->transactionQuery($orderId, $data);
        } elseif ($response->confirmation == 'NOTCONFIRMED' && $response->reason == 'START_AMOUNT_OUT_OF_RANGE') {
            $array = [
                'status' => 'FAILURE',
                'message' => $this->module->l(self::FAILURE_DURRING_MSG, 'chargesmartney'),
            ];
            $data['payment_status'] = 'START_AMOUNT_OUT_OF_RANGE';
            $this->transactionQuery($orderId, $data);
        }
        return $array;
    }


    /**
     * Create transaction and insert sql query
     * @param int $orderId
     * @param $data
     */
    private function transactionQuery(int $orderId, $data)
    {
        $gateway_id = (string)\BlueMedia\OnlinePayments\Model\Gateway::GATEWAY_ID_SMARTNEY;
        $transaction = $this->getTransactionByOrderId($orderId);

        if (empty($transaction)) {
            $data['gateway_id'] = $gateway_id;
            Db::getInstance()->insert('blue_transactions', $data);
        } else {
            unset($data['order_id']);
            Db::getInstance()->update('blue_transactions', $data, 'order_id = '.$orderId);
        }
    }

    /**
     * Get transaction
     * @param $orderId
     * @param $gateway_id
     * @return mixed
     */
    private function getTransactionData($orderId, $gateway_id)
    {
        $query = new DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = '.(int)$orderId)
            ->where('gateway_id = '.pSQL($gateway_id))
            ->select('*');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    /**
     * Validate order
     * @param $cartId
     * @param $amount
     * @param $customer
     */
    private function moduleValidateOrder($cartId, $amount, $customer)
    {
        $this->module->validateOrder(
            $cartId,
            Configuration::get($this->module->name_upper.'_STATUS_WAIT_PAY_ID'),
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

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

namespace BluePayment\Service;

use BluePayment\Config\Config;
use Module;
use PrestaShopLogger;
use Validate;
use Configuration as Cfg;
use Db;
use Manufacturer;
use Product;
use BlueMedia\OnlinePayments\Gateway;
use BluePayment\Until\Helper;
use BluePayment\Analyse\AnalyticsTracking;

class Transactions
{
    /**
     * Payment confirmed
     */
    const TRANSACTION_CONFIRMED = 'CONFIRMED';
    const TRANSACTION_NOTCONFIRMED = 'NOTCONFIRMED';

    /**
     * Payment statuses
     */
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';

    const BM_PREFIX = 'Blue Media - ';

    /**
     * @var array
     */
    private $checkHashArray = [];
    private $module;
    private $orderHistory;

    public function __construct(
        \BluePayment $module,
        \OrderHistory $orderHistory
    ) {
        $this->module = $module;
        $this->orderHistory = $orderHistory;
    }




    /**
     * Reads the data and checks the consistency of the transaction/payment data
     * According to the information obtained from the 'StatusModuleFront' controller
     * @param $response
     * @throws Exception
     */
    public function processStatusPayment($response)
    {
        $transactionXml = $response->transactions->transaction;

        if ($this->validAllTransaction($response)) {
            // Update order and transaction status
            $this->updateStatusTransactionAndOrder($transactionXml);
        } else {
            $message = $this->module->name_upper . ' - Invalid hash: ' . $response->hash;
            // Feedback confirmation about non-authentic transaction
            PrestaShopLogger::addLog(self::BM_PREFIX . $message, 3, null, 'Order', $transactionXml->orderID);
            $this->returnConfirmation(
                $transactionXml->orderID,
                null,
                self::TRANSACTION_NOTCONFIRMED
            );
        }
    }


    /**
     * Update the status of the order, transaction and send email to the customer
     * @param $transaction
     * @throws Exception
     */
    public function updateStatusTransactionAndOrder($transaction)
    {
        Config::getSdk();

        /// Payment status identifiers
        $statusAcceptId = (int)Cfg::get($this->module->name_upper . '_STATUS_ACCEPT_PAY_ID');
        $statusWaitingId = (int)Cfg::get($this->module->name_upper . '_STATUS_WAIT_PAY_ID');
        $statusErrorId = (int)Cfg::get($this->module->name_upper . '_STATUS_ERROR_PAY_ID');

        /// Payment status
        $payment_status = $this->pSql((string)$transaction->paymentStatus);

        /// The transaction id assigned by the gateway
        $remote_id = $this->pSql((string)$transaction->remoteID);

        /// Order id
        $realOrderId = $this->pSql((string)$transaction->orderID);
        $order_id = explode('-', (string) $realOrderId)[0];

        $order = new \OrderCore($order_id);
        $order_payments = $order->getOrderPaymentCollection();


        if (is_object($order_payments)) {
            $order_payment = $order_payments;
        } else {
            $order_payment = new \OrderPaymentCore();
        }

        if (!Validate::isLoadedObject($order)) {
            $message = $this->module->name_upper . ' - Order not found';
            PrestaShopLogger::addLog(self::BM_PREFIX . $message, 3, null, 'Order', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);

            return;
        }

        if (!is_object($order_payment)) {
            $message = $this->module->name_upper . ' - Order payment not found';
            PrestaShopLogger::addLog(self::BM_PREFIX . $message, 3, null, 'OrderPayment', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);
            return;
        }

        $transactionData = [
            'remote_id' => (string) $transaction->remoteID,
            'amount' => (string) $transaction->amount,
            'currency' => (string) $transaction->currency,
            'gateway_id' => (string) $transaction->gatewayID,
            'payment_date' => date('Y-m-d H:i:s', strtotime((string) $transaction->paymentDate)),
            'payment_status' => (string) $transaction->paymentStatus,
            'payment_status_details' => (string) $transaction->paymentStatusDetails,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->updateTransactionQuery($realOrderId, $transactionData);

        $total_paid = $order->total_paid;
        $amount = number_format(round($total_paid, 2), 2, '.', '');
        // Jeśli zamówienie jest otwarte i status zamówienia jest różny od pustej wartości
        if (!$this->isOrderCompleted($order) && $payment_status != '') {
            switch ($payment_status) {
                // Jeśli transakcja została rozpoczęta
                case self::PAYMENT_STATUS_PENDING:
                        $this->changeOrdersStatus($order, $statusWaitingId);
                    break;
//                 Jeśli transakcja została zakończona poprawnie
                case self::PAYMENT_STATUS_SUCCESS:
                    /// Send GA event
                    if (
                        Cfg::get('BLUEPAYMENT_GA_TRACKER_ID') ||
                        (Cfg::get('BLUEPAYMENT_GA4_TRACKER_ID') && Cfg::get('BLUEPAYMENT_GA4_SECRET'))
                    ) {
                        /// Get ga user session
                        $query = new \DbQuery();
                        $query->from('blue_transactions')
                            ->where('order_id like "' . $this->pSQL($order_id) . '-%"')
                            ->where('gtag_state IS NULL')
                            ->select('gtag_uid');
                        $ga_cid = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query, false);

                        if (!empty($ga_cid)) {
                            $args = [];
                            $items = [];
                            $order_ga = new \OrderCore($order_id);

                            if ($order_ga->getProducts()) {
                                $p_key = 0;
                                foreach ($order_ga->getProducts() as $p) {
                                    $brand = null;
                                    $category_name = null;

                                    if ($p['id_manufacturer']) {
                                        $brand = Manufacturer::getNameById($p['id_manufacturer']);
                                    }

                                    $cat = new \Category(
                                        $p['id_category_default'],
                                        \Context::getContext()->language->id
                                    );
                                    if ($cat) {
                                        $category_name = $cat->name;
                                    }

                                    $p_key++;

                                    if (Cfg::get('BLUEPAYMENT_GA_TYPE') === '1') {
                                        $args['pr' . $p_key . 'id'] = $p['product_id'];
                                        $args['pr' . $p_key . 'nm'] = Product::getProductName($p['product_id']);
                                        $args['pr' . $p_key . 'br'] = $brand;
                                        $args['pr' . $p_key . 'ca'] = $category_name;
                                        $args['pr' . $p_key . 'pr'] = $p['total_price_tax_incl'];
                                        $args['pr' . $p_key . 'qt'] = $p['product_quantity'];
                                    } elseif (Cfg::get('BLUEPAYMENT_GA_TYPE') === '2') {
                                        $items[$p_key - 1] = [
                                                'item_id' => $p['product_id'],
                                                'item_name' => Product::getProductName($p['product_id']),
                                                'item_brand' => $brand,
                                                'item_category' => $category_name,
                                                'price' => $p['total_price_tax_incl'],
                                                'quantity' => $p['product_quantity'],
                                        ];
                                    }
                                }
                            }

                            if (Cfg::get('BLUEPAYMENT_GA_TYPE') === '1') {
                                /// GA Universal
                                $analitics = new AnalyticsTracking(Cfg::get('BLUEPAYMENT_GA_TRACKER_ID'), $ga_cid);

                                $args['cu'] = \Context::getContext()->currency->iso_code;
                                $args['ti'] = $order_ga->id_cart . '-' . time();
                                $args['tr'] = $order_ga->total_paid_tax_incl;
                                $args['tt'] = $order_ga->total_paid - $order_ga->total_paid_tax_excl;
                                $args['ts'] = $order_ga->total_shipping_tax_incl;
                                $args['pa'] = 'purchase';
                                $analitics->gaSendEvent('ecommerce', 'purchase', 'accepted', $args);
                            } elseif (Cfg::get('BLUEPAYMENT_GA_TYPE') === '2') {
                                $analitics = new AnalyticsTracking(
                                    Cfg::get('BLUEPAYMENT_GA4_TRACKER_ID'),
                                    $ga_cid,
                                    Cfg::get('BLUEPAYMENT_GA4_SECRET')
                                );

                                $args['events'][] = [
                                    'name' => 'purchase',
                                    'params' => [
                                        'items' => $items,
                                        'currency' => \Context::getContext()->currency->iso_code,
                                        'transaction_id' => $order_ga->id_cart . '-' . time(),
                                        'value' => $order_ga->total_paid_tax_incl,
                                        'tax' => $order_ga->total_paid - $order_ga->total_paid_tax_excl,
                                        'shipping' => $order_ga->total_shipping_tax_incl,
                                    ],
                                ];
                                $args['user_id'] = $order_ga->id_customer;
                                $analitics->ga4SendEvent($args);
                            }

                            /// Reset state
                            $transactionData = [
                                'gtag_state' => 1,
                            ];
                            $this->updateTransactionQuery($order_id, $transactionData);
                        }

                    }

                    $this->changeOrdersStatus($order, $statusAcceptId);

                    if ((string)$transaction->gatewayID == (string)Config::GATEWAY_ID_BLIK) {
                        $transactionData['blik_status'] = (string)$transaction->paymentStatus;
                        $this->updateTransactionQuery($realOrderId, $transactionData);
                    }

                    if (is_object($order_payment)) {
                        $order_payment = $order->getOrderPayments()[0];
                        $order_payment->amount = $amount;
                        $order_payment->transaction_id = $remote_id;
                        $order_payment->update();
                    }

                    break;
                case self::PAYMENT_STATUS_FAILURE:
                        $this->changeOrdersStatus($order, $statusErrorId);
                    break;
                default:
                    break;
            }

            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_CONFIRMED);
        } else {
            $message = $this->module->name_upper . ' - Order status is cancel or payment status unknown';
            PrestaShopLogger::addLog(self::BM_PREFIX . $message, 3, null, 'OrderState', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, $message);
        }
    }


    public function updateTransactionQuery($orderId, $transactionData)
    {
        return Db::getInstance()->update(
            'blue_transactions',
            $transactionData,
            'order_id = \'' . $this->pSQL($orderId) . '\''
        );
    }

    /**
     * Change order states
     * @param $orders
     * @param $statusId
     *
     * @return bool
     */
    public function changeOrderStatus($orders, $statusId): bool
    {
        $i = 0;
        foreach ($orders as $orderId) {
            $order = new \OrderCore($orderId);
            $currentOrderStatus = (int) $order->getCurrentState();
            $existPayment = !$order->hasInvoice();

            if ($currentOrderStatus === $statusId) {
                return false;
            }

            $this->orderHistory->id_order = (int) $orderId;
            $this->orderHistory->changeIdOrderState(
                (int) $statusId,
                $order,
                $existPayment
            );

            if($i === 0) {
                if (!$this->orderHistory->addWithemail(true, [])) {
                    Helper::sendEmail(
                        (int) $orderId,
                        [],
                        $currentOrderStatus
                    );
                }
            }
            $i++;
        }

        return true;
    }

    /**
     * Get all orders ids and change status
     *
     * @param $order
     * @param int $statusId
     */
    public function changeOrdersStatus($order, int $statusId): void
    {
        $ordersArray = [];
        foreach ($this->getBrother($order) as $subOrder) {
            $ordersArray[] = $subOrder['id_order'];
        }
        $orders = array_merge($ordersArray, [$order->id]);

        $this->changeOrderStatus($orders, $statusId);
    }



    /**
     * Get all orders by reference and card id
     */
    public function getBrother($order)
    {
        $sql = new \DbQuery();
        $sql->select('id_order');
        $sql->from('orders');
        $sql->where('reference = "'. $order->reference. '"');
        $sql->where('id_cart = "'. (int) $order->id_cart. '"');

        return Db::getInstance()->executeS($sql);
    }


    /**
     * Checks if the order has been cancelled
     * @param object $order
     */
    public function isOrderCompleted($order): bool
    {
        $status = $order->getCurrentState();
        $stateOrderTab = [Cfg::get('PS_OS_CANCELED')];

        return in_array($status, $stateOrderTab);
    }


    /**
     * @param $realOrderId
     * @param $order_id
     * @param $confirmation
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException|DOMException
     */
    public function returnConfirmation($realOrderId, $order_id, $confirmation)
    {
        if (null === $order_id) {
            $order_id = explode('-', $realOrderId)[0];
        }

        $order = new \Order($order_id);
        $currency = new \Currency($order->id_currency);

        // Partner service id
        $service_id = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SERVICE_PARTNER_ID,
            $currency->iso_code
        );

        /// Shared key
        $shared_key = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SHARED_KEY,
            $currency->iso_code
        );

        /// An array of data from which to generate the hash
        $hash_data = [$service_id, $realOrderId, $confirmation, $shared_key];

        /// hash key
        $hash_confirmation = Helper::generateAndReturnHash($hash_data);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $confirmation_list = $dom->createElement('confirmationList');

        $dom_service_id = $dom->createElement('serviceID', $service_id);
        $confirmation_list->appendChild($dom_service_id);

        $transactions_confirmations = $dom->createElement('transactionsConfirmations');
        $confirmation_list->appendChild($transactions_confirmations);

        $dom_transaction_confirmed = $dom->createElement('transactionConfirmed');
        $transactions_confirmations->appendChild($dom_transaction_confirmed);

        $dom_order_id = $dom->createElement('orderID', $realOrderId);
        $dom_transaction_confirmed->appendChild($dom_order_id);

        $dom_confirmation = $dom->createElement('confirmation', $confirmation);
        $dom_transaction_confirmed->appendChild($dom_confirmation);

        $dom_hash = $dom->createElement('hash', $hash_confirmation);
        $confirmation_list->appendChild($dom_hash);

        $dom->appendChild($confirmation_list);
        echo $dom->saveXML();
    }

    /**
     * Validates the compliance of the received XML
     *
     * @param \SimpleXMLElement $response
     *
     * @return bool
     */
    public function validAllTransaction(\SimpleXMLElement $response): bool
    {
        Config::getSdk();

        $responseOrder = $response->transactions->transaction->orderID;
        if (!$responseOrder) {
            return false;
        }
        $order = explode('-', (string) $responseOrder)[0];
        if (!is_numeric($order)) {
            return false;
        }

        $order = new \OrderCore($order);
        $currency = new \Currency($order->id_currency);

        $service_id = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SERVICE_PARTNER_ID,
            $currency->iso_code
        );
        $shared_key = Helper::parseConfigByCurrency(
            $this->module->name_upper . Config::SHARED_KEY,
            $currency->iso_code
        );

        if ($service_id != $response->serviceID) {
            return false;
        }

        $this->checkHashArray = [];
        $hash = (string)$response->hash;
        $this->checkHashArray[] = (string)$response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->checkInList($trans);
        }
        $this->checkHashArray[] = $shared_key;
        $localHash = hash(Gateway::HASH_SHA256, implode(Config::HASH_SEPARATOR, $this->checkHashArray));

        return $localHash === $hash;
    }

    public function checkInList($list)
    {
        foreach ((array)$list as $row) {
            if (is_object($row)) {
                $this->checkInList($row);
            } else {
                $this->checkHashArray[] = $row;
            }
        }
    }

    public function pSQL($string, $htmlOK = false)
    {
        return Db::getInstance()->escape($string, $htmlOK);
    }
}

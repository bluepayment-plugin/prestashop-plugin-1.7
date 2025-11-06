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

class Istn
{
    private $module;

    public function __construct(\BluePayment $module)
    {
        $this->module = $module;
    }

    private function collectTransactionDataForHash(\SimpleXMLElement $element, array &$hashData): void
    {
        foreach ($element as $childValue) {
            if ($childValue->count() > 0) {
                $this->collectTransactionDataForHash($childValue, $hashData);
            } else {
                $hashData[] = (string) $childValue;
            }
        }
    }

    private function isValidIncomingIstn(\SimpleXMLElement $xml, string $receivedHash, string $istnServiceID): bool
    {
        $hashData = [];
        $hashData[] = $istnServiceID;

        if (!isset($xml->transactions->transaction)) {
            \PrestaShopLogger::addLog('Autopay ISTN Service: No transaction found in ISTN for hash validation.', 3);

            return false;
        }
        $transactionNode = $xml->transactions->transaction; // Access the single transaction directly
        $this->collectTransactionDataForHash($transactionNode, $hashData);

        $sharedKey = null;
        if (isset($transactionNode->currency)) {
            $transactionCurrency = (string) $transactionNode->currency;
            if (!empty($transactionCurrency)) {
                try {
                    $sharedKey = Helper::parseConfigByCurrency(
                        $this->module->name_upper . Config::SHARED_KEY,
                        $transactionCurrency
                    );
                } catch (\Exception $e) {
                    \PrestaShopLogger::addLog('Autopay ISTN Service: Exception while fetching shared key for incoming validation: ' . $e->getMessage(), 3);

                    return false;
                }
            }
        }

        if (!$sharedKey) {
            \PrestaShopLogger::addLog('Autopay ISTN Service: Could not determine shared key for incoming hash validation (missing currency in transaction or config issue).', 3);

            return false;
        }
        $hashData[] = $sharedKey;

        $calculatedHash = Helper::generateAndReturnHash($hashData);

        if ($calculatedHash !== $receivedHash) {
            \PrestaShopLogger::addLog(
                'Autopay ISTN Service: Incoming HASH mismatch. Received: ' . $receivedHash . ', Calculated: ' . $calculatedHash,
                3
            );

            return false;
        }

        return true;
    }

    public function processIstnRequest(\SimpleXMLElement $xml): array
    {
        $istnServiceID = (string) $xml->serviceID;
        $receivedHash = (string) $xml->hash;
        $processedTransactionsForResponse = [];
        $isIncomingIstnAuthentic = $this->isValidIncomingIstn($xml, $receivedHash, $istnServiceID);

        if (!isset($xml->transactions->transaction)) {
            \PrestaShopLogger::addLog('Autopay ISTN Service: No transaction data in ISTN.', 2);

            return ['serviceID' => $istnServiceID, 'processedTransactions' => [], 'authentic' => $isIncomingIstnAuthentic];
        }
        $transaction = $xml->transactions->transaction;

        $istnRemoteOutID = (string) $transaction->remoteOutID;
        $processedTransactionsForResponse[] = [
            'remoteOutID' => $istnRemoteOutID,
            'remoteID' => (string) $transaction->remoteID,
            'amount' => (string) $transaction->amount,
            'currency' => (string) $transaction->currency,
        ];

        if (!$isIncomingIstnAuthentic) {
            \PrestaShopLogger::addLog('Autopay ISTN Service: Incoming request is NOT authentic. Processing stopped.', 2);

            return ['serviceID' => $istnServiceID, 'processedTransactions' => $processedTransactionsForResponse, 'authentic' => false];
        }

        $isRefund = (string) $transaction->isRefund;
        if ($isRefund !== 'true') {
            return [
                'serviceID' => $istnServiceID,
                'processedTransactions' => $processedTransactionsForResponse,
                'authentic' => true,
            ];
        }

        $istnRemoteID = (string) $transaction->remoteID;
        $istnCurrency = (string) $transaction->currency;
        $istnAmount = number_format((float) $transaction->amount, 2, '.', '');

        $db = \Db::getInstance();
        try {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'blue_gateways_refunds` 
                    WHERE `remote_id` = \'' . pSQL($istnRemoteID) . '\'
                      AND `amount` = ' . (float) $istnAmount . '
                      AND `currency` = \'' . pSQL($istnCurrency) . '\'';

            $refundRecord = $db->getRow($sql);

            if (!$refundRecord) {
                \PrestaShopLogger::addLog('Autopay ISTN Service: No matching pending refund found for RemoteID: ' . $istnRemoteID, 2);

                return ['serviceID' => $istnServiceID, 'processedTransactions' => $processedTransactionsForResponse, 'authentic' => true];
            }

            $istnOrderID = $refundRecord['order_id'];
            $processedTransactionsForResponse[0]['orderID'] = $istnOrderID;

            $istnTransferStatus = (string) $transaction->transferStatus;

            $updateData = [
                'remote_out_id' => pSQL($istnRemoteOutID),
                'status' => pSQL($istnTransferStatus),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $where = 'id_blue_gateway_refunds = ' . (int) $refundRecord['id_blue_gateway_refunds'];

            if (!$db->update('blue_gateways_refunds', $updateData, $where)) {
                \PrestaShopLogger::addLog('Autopay ISTN Service: Failed to update blue_gateways_refunds for id ' . $refundRecord['id_blue_gateway_refunds'] . '. DB Error: ' . $db->getMsgError(), 3);

                return ['serviceID' => $istnServiceID, 'processedTransactions' => $processedTransactionsForResponse, 'authentic' => true];
            }

            if ($istnTransferStatus !== 'SUCCESS') {
                \PrestaShopLogger::addLog('Autopay ISTN Service: Refund status is not SUCCESS (' . $istnTransferStatus . '). remote_out_id and status updated. No order status change.', 1);

                return [
                    'serviceID' => $istnServiceID,
                    'processedTransactions' => $processedTransactionsForResponse,
                    'authentic' => true,
                ];
            }

            $order = new \Order((int) $istnOrderID);
            if (!\Validate::isLoadedObject($order)) {
                \PrestaShopLogger::addLog('Autopay ISTN Service: Could not load order ' . $istnOrderID . ' for status update.', 3);

                return ['serviceID' => $istnServiceID, 'processedTransactions' => $processedTransactionsForResponse, 'authentic' => true];
            }

            $history = new \OrderHistory();
            $history->id_order = (int) $order->id;
            $history->id_employee = 0;
            $currentState = (int) $order->getCurrentState();
            $refundStateId = (int) \Configuration::get('PS_OS_REFUND');

            if ($currentState !== $refundStateId) {
                $history->changeIdOrderState($refundStateId, (int) $order->id);
                $history->addWithemail(true, []);
            } else {
                \PrestaShopLogger::addLog('Autopay ISTN Service: Order ' . $order->id . ' is already in REFUNDED state.', 1);
            }
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('Autopay ISTN Service: Exception during DB operations: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 3);
        }

        return ['serviceID' => $istnServiceID, 'processedTransactions' => $processedTransactionsForResponse, 'authentic' => true];
    }

    public function returnConfirmation(?string $serviceID, array $processedTransactions, bool $isIncomingIstnAuthentic): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $confirmationList = $dom->createElement('confirmationList');
        $dom->appendChild($confirmationList);

        $serviceIDForXml = $serviceID ?? '';
        $confirmationList->appendChild($dom->createElement('serviceID', $serviceIDForXml));

        $transactionsConfirmationsNode = $dom->createElement('transactionsConfirmations');
        $confirmationList->appendChild($transactionsConfirmationsNode);

        $hashData = [$serviceIDForXml];
        $confirmationValue = $isIncomingIstnAuthentic ? 'CONFIRMED' : 'NOTCONFIRMED';

        $transactionDataForResponse = !empty($processedTransactions) ? $processedTransactions[0] : null;

        if ($transactionDataForResponse) {
            $transactionConfirmedNode = $dom->createElement('transactionConfirmed');

            $remoteOutIDForXml = $transactionDataForResponse['remoteOutID'] ?? ($transactionDataForResponse['orderID'] ?? '');
            $transactionConfirmedNode->appendChild($dom->createElement('remoteOutID', $remoteOutIDForXml));
            $hashData[] = $remoteOutIDForXml;

            $transactionConfirmedNode->appendChild($dom->createElement('confirmation', $confirmationValue));
            $hashData[] = $confirmationValue;

            $transactionsConfirmationsNode->appendChild($transactionConfirmedNode);
        }

        $sharedKey = null;
        if ($transactionDataForResponse && isset($transactionDataForResponse['remoteOutID'])) {
            try {
                $rawOrderId = null;

                if (isset($transactionDataForResponse['orderID'])) {
                    $rawOrderId = $transactionDataForResponse['orderID'];
                } else {
                    $db = \Db::getInstance();
                    $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'blue_gateways_refunds` 
                            WHERE `remote_id` = \'' . pSQL($transactionDataForResponse['remoteID']) . '\'
                              AND `amount` = ' . (float) $transactionDataForResponse['amount'] . '
                              AND `currency` = \'' . pSQL($transactionDataForResponse['currency']) . '\'';

                    $refundRecord = $db->getRow($sql);

                    if ($refundRecord) {
                        $rawOrderId = $refundRecord['order_id'];
                    }
                }

                if ($rawOrderId) {
                    $orderIdParts = explode('-', (string) $rawOrderId);
                    $orderId = $orderIdParts[0];

                    if (is_numeric($orderId) && (int) $orderId > 0) {
                        $order = new \Order((int) $orderId);
                        if (\Validate::isLoadedObject($order)) {
                            $currency = new \Currency($order->id_currency);
                            if (\Validate::isLoadedObject($currency)) {
                                $sharedKey = Helper::parseConfigByCurrency(
                                    $this->module->name_upper . Config::SHARED_KEY,
                                    $currency->iso_code
                                );
                            } else {
                                \PrestaShopLogger::addLog('Autopay ISTN Service Response: Failed to load currency for order ID ' . $orderId . ' (raw: ' . $rawOrderId . ')', 2);
                            }
                        } else {
                            \PrestaShopLogger::addLog('Autopay ISTN Service Response: Failed to load order for ID ' . $orderId . ' (raw: ' . $rawOrderId . ')', 2);
                        }
                    } else {
                        \PrestaShopLogger::addLog('Autopay ISTN Service Response: Invalid OrderID in transaction for shared key: ' . $orderId . ' (raw: ' . $rawOrderId . ')', 2);
                    }
                }
            } catch (\Exception $e) {
                \PrestaShopLogger::addLog('Autopay ISTN Service Response: Exception getting shared key: ' . $e->getMessage(), 3);
            }
        } elseif (!$transactionDataForResponse) {
            \PrestaShopLogger::addLog('Autopay ISTN Service Response: No transaction data to determine currency for shared key. Response hash may be incorrect.', 2);
        }

        if (isset($sharedKey) && $sharedKey) {
            $hashData[] = $sharedKey;
            $calculatedHash = Helper::generateAndReturnHash($hashData);
        } else {
            $calculatedHash = 'SHARED_KEY_UNAVAILABLE_HASH_INVALID';
            \PrestaShopLogger::addLog('Autopay ISTN Service Response: Shared key for response hash not found. Response hash is invalid.', 3);
        }

        $confirmationList->appendChild($dom->createElement('hash', $calculatedHash));

        return $dom->saveXML();
    }
}

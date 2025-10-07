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

namespace BluePayment\Test\Processor;

use BluePayment\Test\Config\Data\TestDataConfig;
use BluePayment\Test\Repository\TransactionRepository;
use BluePayment\Test\Sender\BlikTransactionSender;
use BluePayment\Test\Validator\BlikTransactionValidator;
use BluePayment\Until\Helper;
use Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class BlikTransactionProcessor
{
    /**
     * @var \BluePayment\Test\Repository\TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var \BluePayment\Test\Sender\BlikTransactionSender
     */
    private $transactionSender;

    /**
     * @var \BluePayment\Test\Validator\BlikTransactionValidator
     */
    private $transactionValidator;

    /**
     * @var \Module
     */
    private $module;

    public function __construct(
        TransactionRepository $transactionRepository,
        BlikTransactionSender $transactionSender,
        BlikTransactionValidator $transactionValidator,
        \Module $module
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->transactionSender = $transactionSender;
        $this->transactionValidator = $transactionValidator;
        $this->module = $module;
    }

    /**
     * Initiate BLIK payment
     */
    public function initiatePayment(\Order $order, string $blikCode): array
    {
        try {
            $currency = new \Currency($order->id_currency);
            $customer = new \Customer($order->id_customer);

            if (!$currency->iso_code || !$customer->email) {
                return [
                    'success' => false,
                    'error' => $this->module->l('Invalid order data'),
                ];
            }

            $serviceId = Helper::parseConfigByCurrency('BLUEPAYMENT_SERVICE_PARTNER_ID', $currency->iso_code);
            $sharedKey = Helper::parseConfigByCurrency('BLUEPAYMENT_SHARED_KEY', $currency->iso_code);

            if (!$serviceId || !$sharedKey) {
                return [
                    'success' => false,
                    'error' => $this->module->l('Missing gateway configuration for currency') . ' ' . $currency->iso_code,
                ];
            }

            $orderTotal = (float) $order->total_paid;
            $orderId = $order->id . '-' . time();
            $amount = number_format($orderTotal, 2, '.', '');
            $customerEmail = $customer->email;

            $transaction = $this->transactionRepository->getTransactionData($orderId, $blikCode);

            if (empty($transaction)) {
                $response = $this->transactionSender->sendRequest(
                    $serviceId,
                    $sharedKey,
                    $orderId,
                    $amount,
                    $currency->iso_code,
                    $customerEmail,
                    $blikCode
                );

                if (!$response) {
                    return [
                        'success' => false,
                        'error' => $this->module->l('Failed to initiate payment'),
                        'details' => [],
                    ];
                }

                $result = $this->transactionValidator->validateRequest($response, $orderId, $blikCode);

                if (isset($result['transaction_data'])) {
                    $transaction = $this->transactionRepository->getTransactionByOrderIdExact($orderId);
                    $this->transactionRepository->saveTransaction($transaction, $orderId, $result['transaction_data']);
                    unset($result['transaction_data']);
                }
            } else {
                $result = $this->transactionValidator->validateTransaction($transaction);
            }

            $result['postOrderId'] = $orderId;

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus(int $orderId): array
    {
        try {
            $order = new \Order($orderId);
            if (!$order->id) {
                return [
                    'success' => false,
                    'error' => $this->module->l('Invalid order ID'),
                ];
            }

            $transaction = $this->transactionRepository->getTransactionByOrderId($orderId);

            if (empty($transaction)) {
                return [
                    'success' => false,
                    'error' => $this->module->l('Transaction not found'),
                    'status' => 'UNKNOWN',
                ];
            }

            return $this->transactionValidator->validateTransaction($transaction);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Wait for transaction status to change
     *
     * @param int $orderId Order ID
     * @param array $targetStatuses Status codes to wait for
     * @param ?int $maxAttempts Maximum number of attempts
     * @param ?int $interval Interval between attempts in seconds
     *
     * @return array Transaction status result
     */
    public function waitForTransactionStatus(
        int $orderId,
        array $targetStatuses = ['SUCCESS'],
        ?int $maxAttempts = null,
        ?int $interval = null
    ): array {
        $maxAttempts = $maxAttempts ?? TestDataConfig::MAX_POLLING_ATTEMPTS;
        $interval = $interval ?? TestDataConfig::POLLING_INTERVAL_SECONDS;
        $timeout = TestDataConfig::POLLING_TIMEOUT_SECONDS;

        $startTime = time();
        $attempts = 0;

        while ($attempts < $maxAttempts && (time() - $startTime) < $timeout) {
            ++$attempts;

            $statusResult = $this->checkTransactionStatus($orderId);

            if (!$statusResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to check transaction status',
                    'details' => $statusResult,
                ];
            }

            $currentStatus = $statusResult['status'] ?? null;

            if (in_array($currentStatus, $targetStatuses)) {
                return [
                    'success' => true,
                    'status' => $currentStatus,
                    'message' => 'Transaction completed with status: ' . $currentStatus,
                    'attempts' => $attempts,
                    'time_elapsed' => time() - $startTime,
                    'details' => $statusResult,
                ];
            }

            if (in_array($currentStatus, ['FAILURE', 'DECLINED'])) {
                return [
                    'success' => false,
                    'status' => $currentStatus,
                    'message' => 'Transaction failed with status: ' . $currentStatus,
                    'attempts' => $attempts,
                    'time_elapsed' => time() - $startTime,
                    'details' => $statusResult,
                ];
            }

            if ($attempts < $maxAttempts) {
                sleep($interval);
            }
        }

        return [
            'success' => false,
            'error' => 'Transaction status polling timeout',
            'attempts' => $attempts,
            'time_elapsed' => time() - $startTime,
        ];
    }
}

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

namespace BluePayment\Test\Checker\Transaction;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Test\Config\Data\TestDataConfig;
use BluePayment\Test\Processor\BlikTransactionProcessor;
use BluePayment\Test\Processor\OrderProcessor;
use BluePayment\Test\Provider\BlikCodeProvider;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class BlikTransactionChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var OrderProcessor
     */
    private $orderProcessor;

    /**
     * @var BlikTransactionProcessor
     */
    private $blikProcessor;

    /**
     * @var BlikCodeProvider
     */
    private $blikCodeProvider;

    public function __construct(
        \Module $module,
        \Context $context,
        OrderProcessor $orderProcessor,
        BlikTransactionProcessor $blikProcessor,
        BlikCodeProvider $blikCodeProvider
    ) {
        $this->module = $module;
        $this->context = $context;
        $this->orderProcessor = $orderProcessor;
        $this->blikProcessor = $blikProcessor;
        $this->blikCodeProvider = $blikCodeProvider;
    }

    /**
     * Check full BLIK payment process
     *
     * @return array
     */
    public function check(): array
    {
        try {
            $orderResult = $this->orderProcessor->createOrder();
            if (!$orderResult['success']) {
                return [
                    'success' => false,
                    'message' => $this->module->l('Failed to create test order'),
                    'details' => $orderResult,
                ];
            }

            $order = $orderResult['order'];
            if (!$order instanceof \Order) {
                return [
                    'success' => false,
                    'message' => $this->module->l('Invalid order object'),
                    'details' => $orderResult,
                ];
            }

            $blikCode = $this->blikCodeProvider->getBlikCode('success');

            $paymentResult = $this->blikProcessor->initiatePayment($order, $blikCode);

            $statusResult = $this->blikProcessor->waitForTransactionStatus(
                (int) $order->id,
                ['SUCCESS'],
                TestDataConfig::MAX_POLLING_ATTEMPTS,
                TestDataConfig::POLLING_INTERVAL_SECONDS
            );

            if (!$statusResult['success']) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => $this->module->l('BLIK transaction failed or timed out'),
                    'details' => $statusResult,
                ];
            }

            return [
                'success' => true,
                'status' => 'success',
                'message' => $this->module->l('BLIK payment process completed successfully'),
                'order_id' => $order->id,
                'transaction_status' => $statusResult['status'],
                'details' => [
                    'order' => $orderResult,
                    'payment' => $paymentResult,
                    'status' => $statusResult,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->module->l('Error during BLIK transaction test'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    public function getName(): string
    {
        return 'BLIK Transaction Process';
    }

    public function getDescription(): string
    {
        return 'Tests the full BLIK payment process from order creation to transaction completion';
    }
}

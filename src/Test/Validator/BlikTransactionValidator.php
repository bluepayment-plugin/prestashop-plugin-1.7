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

namespace BluePayment\Test\Validator;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Validator for BLIK transactions
 */
final class BlikTransactionValidator
{
    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Validate request response
     *
     * @param \SimpleXMLElement $response Response from gateway
     * @param string $orderId Order ID
     * @param string $blikCode BLIK code
     *
     * @return array Validation result with transaction data
     */
    public function validateRequest($response, string $orderId, string $blikCode): array
    {
        $array = [];
        $data = [
            'order_id' => $orderId,
            'blik_code' => $blikCode,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($response->confirmation) && (string) $response->confirmation == 'CONFIRMED') {
            if ((string) $response->paymentStatus == 'PENDING') {
                $array = [
                    'success' => true,
                    'status' => 'PENDING',
                    'message' => $this->module->l('Confirm the operation in your bank\'s application.'),
                ];

                $data['blik_status'] = 'PENDING';
            } elseif ((string) $response->paymentStatus == 'SUCCESS') {
                $array = [
                    'success' => true,
                    'status' => 'SUCCESS',
                    'message' => $this->module->l('Payment has been successfully completed.'),
                ];

                $data['blik_status'] = 'SUCCESS';
            } else {
                $array = [
                    'success' => false,
                    'status' => 'FAILURE',
                    'message' => $this->module->l('The entered code is not valid.'),
                ];
            }
        } elseif (isset($response->confirmation)
            && (string) $response->confirmation == 'NOTCONFIRMED'
            && (string) $response->reason == 'WRONG_TICKET'
        ) {
            $array = [
                'success' => false,
                'status' => 'FAILURE',
                'message' => $this->module->l('The entered code is not valid.'),
            ];
            $data['blik_status'] = 'WRONG_TICKET';
        } elseif (isset($response->confirmation)
            && (string) $response->confirmation == 'NOTCONFIRMED'
            && (string) $response->reason == 'MULTIPLY_PAID_TRANSACTION'
        ) {
            $array = [
                'success' => false,
                'status' => 'FAILURE',
                'message' => $this->module->l('Your BLIK transaction has already been paid for.'),
            ];
            $data['blik_status'] = 'MULTIPLY_PAID_TRANSACTION';
        }

        if (empty($array)) {
            $array = [
                'success' => false,
                'status' => 'FAILURE',
                'message' => $this->module->l('The code has expired. Try again.'),
            ];
        }

        $array['transaction_data'] = $data;

        return $array;
    }

    /**
     * Validate existing transaction
     */
    public function validateTransaction($transaction): array
    {
        $transaction = (object) $transaction;

        if ($transaction->blik_status === 'SUCCESS') {
            return [
                'success' => true,
                'status' => 'SUCCESS',
                'message' => $this->module->l('Payment has been successfully completed.'),
            ];
        }

        if ($transaction->blik_status === 'PENDING') {
            return [
                'success' => true,
                'status' => 'PENDING',
                'message' => $this->module->l('Confirm the operation in your bank\'s application.'),
            ];
        }

        if ($transaction->blik_status === 'WRONG_TICKET') {
            return [
                'success' => false,
                'status' => 'FAILURE',
                'message' => $this->module->l('The entered code is not valid.'),
            ];
        }

        if ($transaction->blik_status === 'MULTIPLY_PAID_TRANSACTION') {
            return [
                'success' => false,
                'status' => 'FAILURE',
                'message' => $this->module->l('Your BLIK transaction has already been paid for.'),
            ];
        }

        return [
            'success' => false,
            'status' => 'FAILURE',
            'message' => $this->module->l('Unknown transaction status.'),
        ];
    }
}

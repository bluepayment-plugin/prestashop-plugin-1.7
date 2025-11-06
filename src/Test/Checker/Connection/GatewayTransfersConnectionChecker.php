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

namespace BluePayment\Test\Checker\Connection;

use BluePayment\Api\BlueAPI;
use BluePayment\Api\BlueGateway;
use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Until\AdminHelper;
use BluePayment\Utility\Converter\ObjectToArrayConverter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GatewayTransfersConnectionChecker implements CheckerInterface
{
    use ObjectToArrayConverter;
    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function check(): array
    {
        if (!($this->module instanceof \BluePayment)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Invalid module type for Gateway transfers check'),
                'details' => ['module_type' => get_class($this->module)],
            ];
        }

        $api = new BlueAPI($this->module);
        $gateway = new BlueGateway($this->module, $api);
        $mode = $api->getApiMode();

        $currencies = AdminHelper::getSortCurrencies();

        if (empty($currencies)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('No currencies available'),
                'details' => [],
            ];
        }

        $results = [];
        $overallStatus = 'success';
        $connectionCount = 0;

        foreach ($currencies as $currency) {
            $currencyCode = $currency['iso_code'];
            $merchantData = $api->getApiMerchantData($currencyCode);

            if (empty($merchantData) || !isset($merchantData[0]) || !isset($merchantData[1])) {
                $results[$currencyCode] = $this->createWarningResult(
                    $currencyCode,
                    $this->module->l('API credentials not configured for currency') . ' ' . $currencyCode,
                    [
                        'service_id_configured' => false,
                        'shared_key_configured' => false,
                    ]
                );

                if ($overallStatus !== 'error') {
                    $overallStatus = 'warning';
                }

                continue;
            }

            $startTime = microtime(true);
            $transfersResult = $this->testGatewayTransfers($gateway, $currencyCode);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            ++$connectionCount;

            if ($transfersResult['success']) {
                $results[$currencyCode] = $this->createSuccessResult(
                    $currencyCode,
                    $this->module->l('Transfers retrieved successfully for currency') . ' ' . $currencyCode,
                    [
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'mode' => $mode,
                        'transfers_count' => $transfersResult['count'],
                        'transfers' => $transfersResult['transfers'] ?? [],
                        'connection_status' => 'connected',
                    ]
                );
            } else {
                $results[$currencyCode] = $this->createWarningResult(
                    $currencyCode,
                    $this->module->l('Failed to retrieve transfers for currency') . ' ' . $currencyCode,
                    [
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'connection_status' => 'failed',
                        'error' => $transfersResult['error'] ?? 'Unknown error',
                    ]
                );

                $overallStatus = 'error';
            }
        }

        if ($connectionCount === 0) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Failed to retrieve transfers for all currencies'),
                'details' => $results,
            ];
        }

        return [
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus),
            'details' => [
                'currencies' => $results,
            ],
        ];
    }

    /**
     * Create success result array
     *
     * @param string $currencyCode Currency code
     * @param string $message Message
     * @param array $additionalDetails Additional details
     *
     * @return array Result array
     */
    private function createSuccessResult(string $currencyCode, string $message, array $additionalDetails = []): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'details' => array_merge(
                ['currency' => $currencyCode],
                $additionalDetails
            ),
        ];
    }

    /**
     * Create warning result array
     *
     * @param string $currencyCode Currency code
     * @param string $message Message
     * @param array $additionalDetails Additional details
     *
     * @return array Result array
     */
    private function createWarningResult(string $currencyCode, string $message, array $additionalDetails = []): array
    {
        return [
            'status' => 'warning',
            'message' => $message,
            'details' => array_merge(
                ['currency' => $currencyCode],
                $additionalDetails
            ),
        ];
    }

    public function getName(): string
    {
        return 'Gateway Transfers';
    }

    public function getDescription(): string
    {
        return 'Checks availability of payment transfers for all configured currencies';
    }

    /**
     * Test gateway transfers for a specific currency
     *
     * @param BlueGateway $gateway Gateway instance
     * @param string $currencyCode Currency code
     *
     * @return array Test result
     */
    private function testGatewayTransfers(BlueGateway $gateway, string $currencyCode): array
    {
        try {
            $api = $gateway->getApi();
            $merchantData = $api->getApiMerchantData($currencyCode);
            $mode = $api->getApiMode();

            $gatewayList = $api->gatewayAccountByCurrency($merchantData, $currencyCode, $mode, AdminHelper::getSortLanguages());

            if ($gatewayList && method_exists($gatewayList, 'getGateways')) {
                $channels = $gatewayList->getGateways();

                $channelsData = $this->convertObjectsToArrays($channels);

                return [
                    'success' => true,
                    'count' => count($channels),
                    'channels' => $channelsData,
                ];
            }

            return [
                'success' => false,
                'count' => 0,
                'error' => 'No transfers returned',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get status message based on overall status
     *
     * @param string $status Status code
     *
     * @return string Status message
     */
    private function getStatusMessage(string $status): string
    {
        if ($status === 'error') {
            return $this->module->l('Failed to retrieve payment transfers for one or more currencies');
        } elseif ($status === 'warning') {
            return $this->module->l('Payment transfers retrieved successfully but some currencies are not configured');
        } else {
            return $this->module->l('Payment transfers retrieved successfully for all configured currencies');
        }
    }
}

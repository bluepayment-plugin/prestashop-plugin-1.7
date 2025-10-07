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

class GatewayChannelsConnectionChecker implements CheckerInterface
{
    use ObjectToArrayConverter;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function check(): array
    {
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
            $channelsResult = $this->testGatewayChannels($gateway, $currencyCode);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            ++$connectionCount;

            if ($channelsResult['success']) {
                $results[$currencyCode] = $this->createSuccessResult(
                    $currencyCode,
                    $this->module->l('Gateway connection successful for currency') . ' ' . $currencyCode,
                    [
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'mode' => $mode,
                        'channels_count' => $channelsResult['count'],
                        'channels' => $channelsResult['channels'],
                        'connection_status' => 'connected',
                    ]
                );
            } else {
                $results[$currencyCode] = $this->createWarningResult(
                    $currencyCode,
                    $this->module->l('Gateway connection failed for currency') . ' ' . $currencyCode,
                    [
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'channels_success' => $channelsResult['success'],
                        'connection_status' => 'failed',
                        'error' => $channelsResult['error'] ?? 'Unknown error',
                    ]
                );

                $overallStatus = 'error';
            }
        }

        if ($connectionCount === 0) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Gateway connection failed for all currencies'),
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
        return 'Gateway Channels';
    }

    public function getDescription(): string
    {
        return 'Checks availability of payment channels for all configured currencies';
    }

    /**
     * Test gateway channels for a specific currency
     *
     * @param BlueGateway $gateway Gateway instance
     * @param string $currencyCode Currency code
     *
     * @return array Test result
     */
    private function testGatewayChannels(BlueGateway $gateway, string $currencyCode): array
    {
        try {
            $api = $gateway->getApi();
            $merchantData = $api->getApiMerchantData($currencyCode);
            $mode = $api->getApiMode();

            $gatewayList = $api->gatewayAccountByCurrency($merchantData, $currencyCode, $mode);

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
                'error' => 'No channels returned',
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
            return $this->module->l('Failed to retrieve payment channels for one or more currencies');
        } elseif ($status === 'warning') {
            return $this->module->l('Payment channels retrieved successfully but some currencies are not configured');
        } else {
            return $this->module->l('Payment channels retrieved successfully for all configured currencies');
        }
    }
}

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
use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Until\AdminHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ApiConnectionChecker implements CheckerInterface
{
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
                'message' => $this->module->l('Invalid module type for API connection check'),
                'details' => ['module_type' => get_class($this->module)],
            ];
        }

        $api = new BlueAPI($this->module);
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
                $results[$currencyCode] = [
                    'status' => 'warning',
                    'message' => $this->module->l('API credentials not configured for currency') . ' ' . $currencyCode,
                    'details' => [
                        'service_id_configured' => false,
                        'shared_key_configured' => false,
                    ],
                ];

                if ($overallStatus !== 'error') {
                    $overallStatus = 'warning';
                }

                continue;
            }

            [$serviceId, $sharedKey] = $merchantData;

            $startTime = microtime(true);
            $isConnected = $api->isConnectedAPI($serviceId, $sharedKey, $mode);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            ++$connectionCount;

            if ($isConnected) {
                $results[$currencyCode] = [
                    'status' => 'success',
                    'message' => $this->module->l('API connection successful for currency') . ' ' . $currencyCode,
                    'details' => [
                        'service_id' => $serviceId,
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'auth_status' => 'valid',
                    ],
                ];
            } else {
                $results[$currencyCode] = [
                    'status' => 'error',
                    'message' => $this->module->l('API connection failed for currency') . ' ' . $currencyCode,
                    'details' => [
                        'service_id' => $serviceId,
                        'response_time' => $responseTime,
                        'response_time_formatted' => $responseTime . 'ms',
                        'auth_status' => 'invalid',
                    ],
                ];

                $overallStatus = 'error';
            }
        }

        if ($connectionCount === 0) {
            return [
                'status' => 'error',
                'message' => $this->module->l('API connection failed for all currencies'),
                'details' => $results,
            ];
        }

        return [
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus),
            'details' => [
                'currencies' => $results,
                'mode' => $mode,
            ],
        ];
    }

    private function getStatusMessage(string $status): string
    {
        if ($status === 'error') {
            return $this->module->l('API connection failed for one or more currencies');
        } elseif ($status === 'warning') {
            return $this->module->l('API connection successful but some currencies are not configured');
        } else {
            return $this->module->l('API connection successful for all configured currencies');
        }
    }

    public function getName(): string
    {
        return 'API Connection';
    }

    public function getDescription(): string
    {
        return 'Checks connection to Autopay API for all configured currencies';
    }
}

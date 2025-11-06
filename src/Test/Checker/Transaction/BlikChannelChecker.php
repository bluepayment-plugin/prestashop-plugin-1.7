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

use BluePayment\Api\BlueAPI;
use BluePayment\Api\BlueGateway;
use BluePayment\Config\Config;
use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Until\AdminHelper;
use BluePayment\Utility\Converter\ObjectToArrayConverter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BlikChannelChecker implements CheckerInterface
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
                'message' => $this->module->l('Invalid module type for BLIK channel check'),
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
        $blikAvailableInAnyCurrency = false;

        foreach ($currencies as $currency) {
            $currencyCode = $currency['iso_code'];
            $merchantData = $api->getApiMerchantData($currencyCode);

            if (empty($merchantData) || !isset($merchantData[0]) || !isset($merchantData[1])) {
                $results[$currencyCode] = [
                    'status' => 'warning',
                    'message' => $this->module->l('API credentials not configured for currency') . ' ' . $currencyCode,
                    'details' => [
                        'currency' => $currencyCode,
                        'service_id_configured' => false,
                        'shared_key_configured' => false,
                    ],
                ];

                if ($overallStatus !== 'error') {
                    $overallStatus = 'warning';
                }

                continue;
            }

            $channelsResult = $this->checkBlikChannel($gateway, $currencyCode);

            if ($channelsResult['success']) {
                if ($channelsResult['blik_available']) {
                    $blikAvailableInAnyCurrency = true;
                    $results[$currencyCode] = [
                        'status' => 'success',
                        'message' => $this->module->l('BLIK payment channel is available for currency') . ' ' . $currencyCode,
                        'details' => [
                            'currency' => $currencyCode,
                            'mode' => $mode,
                            'blik_available' => true,
                            'blik_channel' => $channelsResult['blik_channel'],
                        ],
                    ];
                } else {
                    $results[$currencyCode] = [
                        'status' => 'warning',
                        'message' => $this->module->l('BLIK payment channel is not available for currency') . ' ' . $currencyCode,
                        'details' => [
                            'currency' => $currencyCode,
                            'mode' => $mode,
                            'blik_available' => false,
                        ],
                    ];

                    if ($overallStatus !== 'error') {
                        $overallStatus = 'warning';
                    }
                }
            } else {
                $results[$currencyCode] = [
                    'status' => 'error',
                    'message' => $this->module->l('Failed to check BLIK payment channel for currency') . ' ' . $currencyCode,
                    'details' => [
                        'currency' => $currencyCode,
                        'error' => $channelsResult['error'] ?? 'Unknown error',
                    ],
                ];

                $overallStatus = 'error';
            }
        }

        if (!$blikAvailableInAnyCurrency && $overallStatus !== 'error') {
            $overallStatus = 'warning';
        }

        return [
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus, $blikAvailableInAnyCurrency),
            'details' => [
                'currencies' => $results,
                'blik_available_in_any_currency' => $blikAvailableInAnyCurrency,
            ],
        ];
    }

    public function getName(): string
    {
        return 'BLIK Payment Channel';
    }

    public function getDescription(): string
    {
        return 'Checks if BLIK payment channel is available for configured currencies';
    }

    /**
     * Check if BLIK channel is available for a specific currency
     *
     * @param BlueGateway $gateway Gateway instance
     * @param string $currencyCode Currency code
     *
     * @return array Check result
     */
    private function checkBlikChannel(BlueGateway $gateway, string $currencyCode): array
    {
        try {
            $api = $gateway->getApi();
            $merchantData = $api->getApiMerchantData($currencyCode);
            $mode = $api->getApiMode();

            $gatewayList = $api->gatewayAccountByCurrency($merchantData, $currencyCode, $mode);

            if ($gatewayList && method_exists($gatewayList, 'getGateways')) {
                $channels = $gatewayList->getGateways();
                $blikChannel = null;

                foreach ($channels as $channel) {
                    if ($channel->getGatewayId() == Config::GATEWAY_ID_BLIK) {
                        $blikChannel = $this->convertObjectsToArrays([$channel])[0];
                        break;
                    }
                }

                return [
                    'success' => true,
                    'blik_available' => $blikChannel !== null,
                    'blik_channel' => $blikChannel,
                ];
            }

            return [
                'success' => false,
                'blik_available' => false,
                'error' => 'No channels returned',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'blik_available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get status message based on overall status and BLIK availability
     *
     * @param string $status Status code
     * @param bool $blikAvailable Whether BLIK is available in any currency
     *
     * @return string Status message
     */
    private function getStatusMessage(string $status, bool $blikAvailable): string
    {
        if ($status === 'error') {
            return $this->module->l('Failed to check BLIK payment channel for one or more currencies');
        } elseif ($status === 'warning') {
            if ($blikAvailable) {
                return $this->module->l('BLIK payment channel is available but not for all currencies');
            } else {
                return $this->module->l('BLIK payment channel is not available for any currency');
            }
        } else {
            return $this->module->l('BLIK payment channel is available for all configured currencies');
        }
    }
}

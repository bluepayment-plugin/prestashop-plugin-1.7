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

namespace BluePayment\Test\Checker\Common;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Until\AdminHelper;
use BluePayment\Until\Helper;
use Configuration;
use Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for module configuration
 */
final class ConfigurationChecker implements CheckerInterface
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
     * Required configuration keys
     *
     * @var array
     */
    private $requiredConfigKeys = [
        'BLUEPAYMENT_TEST_ENV',
        'BLUEPAYMENT_STATUS_WAIT_PAY_ID',
        'BLUEPAYMENT_STATUS_ACCEPT_PAY_ID',
        'BLUEPAYMENT_STATUS_ERROR_PAY_ID',
    ];

    /**
     * Klucze konfiguracyjne specyficzne dla walut
     *
     * @var array
     */
    private $currencyConfigKeys = [
        'BLUEPAYMENT_SHARED_KEY',
        'BLUEPAYMENT_SERVICE_PARTNER_ID',
    ];

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function check(): array
    {
        if (!$this->module->active) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Autopay module is not active'),
                'details' => [
                    'module_active' => false,
                ],
            ];
        }

        $missingConfig = [];
        $missingCurrencyConfig = [];

        foreach ($this->requiredConfigKeys as $key) {
            if (!\Configuration::hasKey($key) || \Configuration::get($key) === '') {
                $missingConfig[] = $key;
            }
        }

        $currencies = AdminHelper::getSortCurrencies();
        foreach ($this->currencyConfigKeys as $field) {
            foreach ($currencies as $currency) {
                $value = Helper::parseConfigByCurrency($field, $currency['iso_code']);
                if ($value === '') {
                    $missingCurrencyConfig[] = $field . '_' . $currency['iso_code'];
                }
            }
        }

        if (!empty($missingConfig)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Missing required configuration parameters'),
                'details' => [
                    'module_active' => true,
                    'missing_config' => $missingConfig,
                ],
            ];
        }

        if (!empty($missingCurrencyConfig)) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('Missing currency-specific configuration parameters'),
                'details' => [
                    'module_active' => true,
                    'missing_currency_config' => $missingCurrencyConfig,
                ],
            ];
        }

        $invalidServiceIds = [];
        foreach ($currencies as $currency) {
            $serviceId = Helper::parseConfigByCurrency('BLUEPAYMENT_SERVICE_PARTNER_ID', $currency['iso_code']);
            if ($serviceId !== '' && !is_numeric($serviceId)) {
                $invalidServiceIds['BLUEPAYMENT_SERVICE_PARTNER_ID_' . $currency['iso_code']] = $serviceId;
            }
        }

        if (!empty($invalidServiceIds)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Service Partner ID is not valid for some currencies'),
                'details' => [
                    'module_active' => true,
                    'invalid_service_ids' => $invalidServiceIds,
                ],
            ];
        }

        $currencyConfigs = [];
        foreach ($currencies as $currency) {
            $currencyInfo = [
                'iso_code' => $currency['iso_code'],
                'name' => $currency['name'],
            ];

            foreach ($this->currencyConfigKeys as $field) {
                $currencyInfo[$field] = Helper::parseConfigByCurrency($field, $currency['iso_code']);
            }

            $currencyConfigs[] = $currencyInfo;
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('Module configuration is valid'),
            'details' => [
                'module_active' => true,
                'config_valid' => true,
                'environment' => \Configuration::get('BLUEPAYMENT_TEST_ENV') ? 'test' : 'production',
                'payment_statuses' => [
                    'wait' => \Configuration::get('BLUEPAYMENT_STATUS_WAIT_PAY_ID'),
                    'accept' => \Configuration::get('BLUEPAYMENT_STATUS_ACCEPT_PAY_ID'),
                    'error' => \Configuration::get('BLUEPAYMENT_STATUS_ERROR_PAY_ID'),
                ],
                'quantity_currencies' => count($currencies),
                'currency_configs' => $currencyConfigs,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->module->l('Module Configuration Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the module is properly configured');
    }
}

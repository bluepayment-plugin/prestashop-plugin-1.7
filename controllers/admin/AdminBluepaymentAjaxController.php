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
if (!defined('_PS_VERSION_')) {
    exit;
}

use BlueMedia\ProductFeed\Configuration\FeedConfiguration;
use BlueMedia\ProductFeed\Menager\FileMenager;
use BlueMedia\ProductFeed\Remover\FileRemover;
use BluePayment\Analyse\Amplitude;
use BluePayment\Api\BlueAPI;
use BluePayment\Api\BlueGateway;
use BluePayment\Config\Config;
use BluePayment\Until\AdminHelper;
use BluePayment\Until\Helper;
use Configuration as Cfg;

class AdminBluepaymentAjaxController extends ModuleAdminController
{
    /** @var BluePayment */
    public $module;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ajax die method compatible with different PrestaShop versions
     *
     * @param string $value Value to output
     * @param string|null $controller Controller name
     * @param string|null $method Method name
     *
     * @phpstan-ignore-next-line Method exists in some PrestaShop versions
     */
    protected function ajaxDie($value = null, $controller = null, $method = null)
    {
        if (method_exists(get_parent_class($this), 'ajaxDie')) {
            /* @phpstan-ignore-next-line */
            return parent::ajaxDie($value, $controller, $method);
        }

        exit($value);
    }

    public function initContent(): void
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $this->ajax = true;
    }

    public function displayAjaxReloadPaymentsGateway()
    {
        $link = new Link();
        $controller = $link->getAdminLink('AdminBluepaymentPayments');

        exit(
            Tools::redirectAdmin($controller)
        );
    }

    public function ajaxProcessSaveConfiguration()
    {
        try {
            foreach (Helper::getFields() as $configField) {
                $value = Tools::getValue($configField, Configuration::get($configField));
                Configuration::updateValue($configField, $value);
            }

            foreach (Helper::getFieldsMultiple() as $configField) {
                $fieldReplace = str_replace('[]', '', $configField);
                $value = Tools::getValue($fieldReplace, Configuration::get($fieldReplace));
                Configuration::updateValue($fieldReplace, is_array($value) ? implode(',', array_map('intval', $value)) : '');
            }

            $paymentName = [];
            $paymentGroupName = [];

            foreach (Language::getLanguages(true) as $lang) {
                $paymentName[$lang['id_lang']]
                    = Tools::getValue($this->module->name_upper . '_PAYMENT_NAME_' . $lang['id_lang']);
                $paymentGroupName[$lang['id_lang']]
                    = Tools::getValue($this->module->name_upper . '_PAYMENT_GROUP_NAME_' . $lang['id_lang']);
            }

            $serviceId = [];
            $sharedKey = [];

            foreach (AdminHelper::getSortCurrencies() as $currency) {
                $parseServiceId = Tools::getValue(
                    $this->module->name_upper . '_SERVICE_PARTNER_ID_' . $currency['iso_code']
                );
                $parseHashKey = Tools::getValue(
                    $this->module->name_upper . '_SHARED_KEY_' . $currency['iso_code']
                );

                if ($parseServiceId && $parseHashKey) {
                    $api = new BlueAPI($this->module);

                    $testMode = Cfg::get($this->module->name_upper . '_TEST_ENV');
                    $gatewayMode = $testMode ? 'sandbox' : 'live';

                    $connect_status = $api->isConnectedAPI($parseServiceId, $parseHashKey, $gatewayMode);

                    if ($connect_status) {
                        PrestaShopLogger::addLog(
                            Config::API_AUTHENTICATION_SUCCESS . ' - currency ' . $currency['iso_code'],
                            1
                        );
                        $data = [
                            'events' => [
                                'event_type' => Config::API_AUTHENTICATION_SUCCESS,
                                'user_properties' => [
                                    Config::PLUGIN_AUTH => true,
                                ],
                            ],
                        ];
                    } else {
                        PrestaShopLogger::addLog(
                            Config::API_AUTHENTICATION_FAILED . ' - currency ' . $currency['iso_code'],
                            1
                        );
                        $data = [
                            'events' => [
                                'event_type' => Config::API_AUTHENTICATION_FAILED,
                                'user_properties' => [
                                    Config::PLUGIN_AUTH => false,
                                ],
                            ],
                        ];
                    }

                    $amplitude = Amplitude::getInstance();
                    $amplitude->sendEvent($data);
                } else {
                    PrestaShopLogger::addLog(
                        Config::API_AUTHENTICATION_FAILED . ' wrong key - currency ' . $currency['iso_code'],
                        1
                    );

                    $data = [
                        'events' => [
                            'event_type' => Config::API_AUTHENTICATION_FAILED,
                            'user_properties' => [
                                Config::PLUGIN_AUTH => false,
                            ],
                        ],
                    ];

                    $amplitude = Amplitude::getInstance();
                    $amplitude->sendEvent($data);
                }

                $serviceId[$currency['iso_code']] = $parseServiceId;
                $sharedKey[$currency['iso_code']] = $parseHashKey;
            }

            Configuration::updateValue($this->module->name_upper . '_PAYMENT_NAME', $paymentName);
            Configuration::updateValue($this->module->name_upper . '_PAYMENT_GROUP_NAME', $paymentGroupName);
            Configuration::updateValue($this->module->name_upper . Config::SERVICE_PARTNER_ID, json_encode($serviceId));
            Configuration::updateValue($this->module->name_upper . Config::SHARED_KEY, json_encode($sharedKey));

            $gateway = new BlueGateway($this->module, new BlueAPI($this->module));
            $gateway->getChannels();
            $gateway->getTransfers();

            if (!Configuration::get($this->module->name_upper . FeedConfiguration::AP_SUFFIX_ENABLED_PRODUCT_FEED)) {
                $fileMenage = new FileMenager();
                $fileRemover = new FileRemover($fileMenage);
                $fileRemover->removeAllFeedFile();
            }

            exit(json_encode(['success' => true]));
        } catch (Exception $exception) {
            PrestaShopLogger::addLog(
                'Autopay - Ajax Error',
                4
            );
            exit(json_encode(['success' => false]));
        }
    }
}

<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use BlueMedia\OnlinePayments\Gateway;
use BlueMedia\OnlinePayments\Model\Gateway as GatewayModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

require dirname(__FILE__) . '/vendor/autoload.php';

class BluePayment extends PaymentModule
{
    public $name_upper;
    /**
     * Haki używane przez moduł
     *
     * @var array
     */
    protected $hooks
        = [
            'header',
            'paymentOptions',
            'paymentReturn',
            'orderConfirmation',
            'displayBackOfficeHeader'
        ];

    private $checkHashArray = [];

    /**
     * Stałe statusów płatności
     */
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';

    /**
     * Stałe potwierdzenia autentyczności transakcji
     */
    const TRANSACTION_CONFIRMED = 'CONFIRMED';
    const TRANSACTION_NOTCONFIRMED = 'NOTCONFIRMED';

    public function __construct()
    {
        $this->name = 'bluepayment';
        $this->name_upper = Tools::strtoupper($this->name);

        require_once dirname(__FILE__) . '/config/config.inc.php';

        $this->tab = 'payments_gateways';
        $this->version = '2.6.5';
        $this->author = 'Blue Media S.A.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->module_key = '7dac119ed21c46a88632206f73fa4104';

        parent::__construct();

        $this->displayName = $this->l('Online payment BM');
        $this->description = $this->l('Plugin supports online payments implemented by payment gateway Blue Media company.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module
     *
     * @return bool
     */

    public function install()
    {
        if (parent::install()) {
            $this->installDb();
            $this->installTab();
            $this->addTabInPayments();

            foreach ($this->hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }
            $this->installConfigurationTranslations();
            $this->addOrderStatuses();

            // Domyślne ustawienie aktywnego trybu testowego
            Configuration::updateValue($this->name_upper . '_TEST_ENV', 1);
            Configuration::updateValue($this->name_upper . '_SHOW_PAYWAY', 0);
            Configuration::updateValue($this->name_upper . '_SHOW_PAYWAY_LOGO', 1);
            Configuration::updateValue($this->name_upper . '_SHOW_BANER', 0);
            Configuration::updateValue($this->name_upper . '_PAYMENT_NAME', 'Pay via BlueMedia');
            Configuration::updateValue($this->name_upper . '_PAYMENT_NAME_EXTRA', 'After order redirect to BlueMedia payment system');

            return true;
        }

        return false;
    }


    public function addOrderStatuses()
    {
        try {
            CustomStatus::addOrderStates($this->context->language->id, $this->name_upper);
            return true;
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Add statuses - error', 4);
        }
    }


    /**
     * Remove module
     *
     * @return bool
     */

    public function uninstall()
    {

        $this->uninstallDb();
        $this->uninstallTab();
        $this->removeTabInPayments();

        if (parent::uninstall()) {
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }
            }

            foreach ($this->configFields() as $configField) {
                Configuration::deleteByName($configField);
            }

            Configuration::deleteByName($this->name_upper . '_SHARED_KEY');
            Configuration::deleteByName($this->name_upper . '_SERVICE_PARTNER_ID');

            return true;
        }

        return false;
    }


    /**
     * Install tab controller AdminBluepaymentController
     *
     * @return bool
     */

    public function installTab()
    {
        try {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminBluepayment';
            $tab->name = [];
            $tab->visible = true;
            $tab->id_parent = -1;

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] =
                    $this->trans('Blue Media settings', [], 'Modules.Bluepayment', $lang['locale']);
            }

            $tab->module = $this->name;

            return $tab->add();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Error adding adminBluepaymentController', 4);

            return false;
        }
    }


    /**
     * Remove tab controller AdminBluepaymentController
     *
     * @return bool
     */
    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminBluepayment');

        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return false;
    }


    /**
     * The method adds Blue media payment to the list in the payment settings
     *
     * @return bool
     */

    public function addTabInPayments()
    {

        try {
            $payment_tab = new BlueTabPayment();
            $payment_tab->addTab();
            return true;
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Payment tab creation - error', 4);
            return false;
        }
    }

    /**
     * The method remove Blue media payment
     *
     * @return bool
     */

    public function removeTabInPayments()
    {

        try {
            $payment_tab = new BlueTabPayment();
            $payment_tab->removeTab();
            return true;
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Payment tab remove - error', 4);
            return false;
        }
    }

    /**
     * Hook to back office header: <head></head>
     */

    public function hookDisplayBackOfficeHeader($params)
    {
        $this->addTabInPayments();
    }


    /**
     * Post form method
     *
     * @return string
     */

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($this->configFields() as $configField) {
                $value = Tools::getValue($configField, Configuration::get($configField));

                Configuration::updateValue($configField, $value);
            }
            $paymentName = [];
            $paymentNameExtra = [];

            foreach (Language::getLanguages(true) as $lang) {
                $paymentName[$lang['id_lang']] =
                    Tools::getValue($this->name_upper . '_PAYMENT_NAME_' . $lang['id_lang']);
                $paymentNameExtra[$lang['id_lang']] =
                    Tools::getValue($this->name_upper . '_PAYMENT_NAME_EXTRA_' . $lang['id_lang']);
            }

            $serviceId = [];
            $sharedKey = [];

            foreach (Currency::getCurrencies() as $currency) {
                $serviceId[$currency['iso_code']] =
                    Tools::getValue($this->name_upper . '_SERVICE_PARTNER_ID_' . $currency['iso_code']);
                $sharedKey[$currency['iso_code']] =
                    Tools::getValue($this->name_upper . '_SHARED_KEY_' . $currency['iso_code']);
            }

            Configuration::updateValue($this->name_upper . '_PAYMENT_NAME', $paymentName);
            Configuration::updateValue($this->name_upper . '_PAYMENT_NAME_EXTRA', $paymentNameExtra);
            Configuration::updateValue($this->name_upper . '_SERVICE_PARTNER_ID', serialize($serviceId));
            Configuration::updateValue($this->name_upper . '_SHARED_KEY', serialize($sharedKey));

            $gateway = new BlueGateway();
            $gateway->syncGateways();

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        if (Tools::isSubmit('refreshGateways')) {
            $gateway = new BlueGateway();
            $gateway->syncGateways();

            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output . $this->renderForm();
    }


    public function installDb()
    {
        require_once dirname(__FILE__) . '/sql/install.php';
    }

    public function removeOrderStatuses()
    {
        try {
            CustomStatus::removeOrderStates();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Remove statuses - error', 4);
        }
    }

    public function uninstallDb()
    {
        try {
            require_once dirname(__FILE__) . '/sql/uninstall.php';
            $this->removeOrderStatuses();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - The table cannot be deleted from the database', 4);
        }
    }


    /**
     * Generate form
     *
     * @return string
     */

    public function renderForm()
    {
        $render = '';
        $fields_form = [];

        $id_default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $statuses = OrderState::getOrderStates($id_default_lang);

        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings', 'bluepayment'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Use test gateway'),
                    'name' => $this->name_upper . '_TEST_ENV',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show payway in shop'),
                    'name' => $this->name_upper . '_SHOW_PAYWAY',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show logo payways'),
                    'name' => $this->name_upper . '_SHOW_PAYWAY_LOGO',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show baner'),
                    'name' => $this->name_upper . '_SHOW_BANER',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_WAIT_PAY_ID',
                    'label' => $this->l('Status waiting payment'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_ACCEPT_PAY_ID',
                    'label' => $this->l('Status accept payment'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_ERROR_PAY_ID',
                    'label' => $this->l('Status error payment'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Payment name'),
                    'name' => $this->name_upper . '_PAYMENT_NAME',
                    'size' => 40,
                    'lang' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Payment name extra'),
                    'name' => $this->name_upper . '_PAYMENT_NAME_EXTRA',
                    'size' => 40,
                    'lang' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];


        foreach (Currency::getCurrencies() as $currency) {
            $fields_form['currency_' . $currency['iso_code']] = [
                'form' => [
                    'legend' => [
                        'title' =>
                            $this->l('Currency settings: ') . $currency['name'] . ' (' . $currency['iso_code'] . ')',
                        'icon' => 'icon-cog',
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('Service partner ID'),
                            'name' => $this->name_upper . '_SERVICE_PARTNER_ID_' . $currency['iso_code'],
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Shared key'),
                            'name' => $this->name_upper . '_SHARED_KEY_' . $currency['iso_code'],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }

        $helper = new HelperForm();

        // Moduł, token i currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Domyślny język
        $helper->default_form_language = $id_default_lang;
        $helper->allow_employee_form_lang = $id_default_lang;


        // Tytuł i belka narzędzi
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' =>
                [
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                        '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ],
            'back' =>
                [
                    'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list'),
                ],
        ];

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $render .= $helper->generateForm($fields_form);
        $render .= $this->renderAdditionalOptionsList();

        return $render;
    }

    public function getListContent()
    {
        $gateway = Db::getInstance((bool)_PS_USE_SQL_SLAVE_)->executeS('SELECT id, gateway_id, gateway_name, gateway_logo_url, gateway_type, position, bank_name, gateway_currency, gateway_status, position FROM `' . _DB_PREFIX_ . 'blue_gateways`');
        return $gateway;
    }


    public function getGatewaysListFields()
    {
        return [
            'gateway_id' => [
                'title' => $this->l('Gateway ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'search' => false,
            ],
            'bank_name' => [
                'title' => $this->l('Bank Name'),
                'class' => 'fixed-width-xs',
            ],
            'gateway_name' => [
                'title' => $this->l('Name'),
                'orderby' => false,
            ],
            'gateway_logo_url' => [
                'title' => $this->l('Logo'),
                'callback' => 'displayGatewayLogo',
                'callback_object' => Module::getInstanceByName($this->name),
                'orderby' => false,
                'class' => 'fixed-width-xs',
                'search' => false,
            ],
            'gateway_currency' => [
                'title' => $this->l('Currency'),
                'align' => 'center',
                'search' => false,
            ],
            'position' => [
                'title' => $this->l('Position'),
                'filter_key' => 'position',
                'position' => 'position',
                'class' => 'fixed-width-xs',
                'ajax' => true,
                'align' => 'center',
                'search' => false,
            ],
            'gateway_status' => [
                'title' => $this->l('Status'),
                'active' => 'gateway_status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false,
                'search' => false,
            ],




        ];
    }


    public function displayGatewayLogo($gatewayLogo)
    {
        return '<img width="65" class="img-fluid" src="' . $gatewayLogo . '" />';
    }


    protected function renderAdditionalOptionsList()
    {

        $helper = new HelperList();
        $helper->table = 'blue_gateways';
        $helper->module = $this;
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id';
        $helper->actions = ['edit'];
        $helper->title = $this->l('Blue Media Payment channels management');
        $helper->currentIndex = Context::getContext()->link->getAdminLink('AdminBluepayment');

        $content = $this->getListContent();

        $helper->token = Tools::getAdminTokenLite('AdminBluepayment');
        $helper->listTotal = count($content);
        $helper->position_identifier = 'position';
        $helper->orderBy = 'position';
        $helper->orderWay = 'asc';

        $helper->show_toolbar = true;

        $helper->toolbar_btn = [
            'refresh' =>
                [
                    'desc' => $this->l('Refresh'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&refreshGateways&token=' . Tools::getAdminTokenLite('AdminModules'),
                ],
        ];

        return $helper->generateList($content, $this->getGatewaysListFields());
    }


    /**
     * Get form values
     *
     * @return array
     */

    public function getConfigFieldsValues()
    {
        $data = [];

        foreach ($this->configFields() as $configField) {
            $data[$configField] = Tools::getValue($configField, Configuration::get($configField));
        }

        foreach (Language::getLanguages(true) as $lang) {
            $data[$this->name_upper . '_PAYMENT_NAME'][$lang['id_lang']] =
                Configuration::get($this->name_upper . '_PAYMENT_NAME', $lang['id_lang']);
            $data[$this->name_upper . '_PAYMENT_NAME_EXTRA'][$lang['id_lang']] =
                Configuration::get($this->name_upper . '_PAYMENT_NAME_EXTRA', $lang['id_lang']);
        }

        foreach (Currency::getCurrencies() as $currency) {
            $data[$this->name_upper . '_SERVICE_PARTNER_ID_' . $currency['iso_code']] =
                $this->parseConfigByCurrency($this->name_upper . '_SERVICE_PARTNER_ID', $currency['iso_code']);
            $data[$this->name_upper . '_SHARED_KEY_' . $currency['iso_code']] =
                $this->parseConfigByCurrency($this->name_upper . '_SHARED_KEY', $currency['iso_code']);
        }

        return $data;
    }

    public function parseConfigByCurrency($key, $currencyIsoCode)
    {
        $data = Tools::unSerialize(Configuration::get($key));

        return is_array($data) && array_key_exists($currencyIsoCode, $data) ? $data[$currencyIsoCode] : '';
    }

    public function configFields()
    {
        return [
            $this->name_upper . '_STATUS_WAIT_PAY_ID',
            $this->name_upper . '_STATUS_ACCEPT_PAY_ID',
            $this->name_upper . '_STATUS_ERROR_PAY_ID',
            $this->name_upper . '_PAYMENT_NAME',
            $this->name_upper . '_PAYMENT_NAME_EXTRA',
            $this->name_upper . '_SHOW_PAYWAY',
            $this->name_upper . '_SHOW_PAYWAY_LOGO',
            $this->name_upper . '_SHOW_BANER',
            $this->name_upper . '_TEST_ENV',
        ];
    }


    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     * @return array|null
     */
    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return null;
        }

        $currency = $this->context->currency;

        $serviceId = $this->parseConfigByCurrency($this->name_upper . '_SERVICE_PARTNER_ID', $currency->iso_code);
        $sharedKey = $this->parseConfigByCurrency($this->name_upper . '_SHARED_KEY', $currency->iso_code);
        $paymentDataCompleted = !empty($serviceId) && !empty($sharedKey);

        if ($paymentDataCompleted === false) {
            return null;
        }

        $moduleLink = $this->context->link->getModuleLink('bluepayment', 'payment', [], true);
        $blik = false;
        $gpay = false;
        $smartney = false;
        $iframe = false;
        $cardGateway = false;

        require_once dirname(__FILE__) . '/sdk/index.php';

        if (Configuration::get($this->name_upper . '_SHOW_PAYWAY')) {
            $gateways = new PrestaShopCollection('BlueGateway', $this->context->language->id);
            $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_BLIK);
            $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_IFRAME);
            $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_CARD);
            $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_GOOGLE_PAY);
            $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_SMARTNEY);
            $gateways->where('gateway_status', '=', 1);
            $gateways->where('gateway_currency', '=', $currency->iso_code);
            $blik = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_BLIK, $currency->iso_code);
            $iframe = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_IFRAME, $currency->iso_code);
            $cardGateway = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_CARD, $currency->iso_code);
            $gpay = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_GOOGLE_PAY, $currency->iso_code);
            $smartney = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_SMARTNEY, $currency->iso_code);
            $gateways->orderBy('position');
            $gateways = $gateways->getResults();
        } else {
            $gateways = array(
                'gateways_payments' => false
            );
        }

        $this->smarty->assign([
            'module_link' => $moduleLink,
            'ps_version' => _PS_VERSION_,
            'module_dir' => $this->_path,
            'payment_name' => Configuration::get($this->name_upper . '_PAYMENT_NAME', $this->context->language->id),
            'payment_name_extra' =>
                Configuration::get($this->name_upper . '_PAYMENT_NAME_EXTRA', $this->context->language->id),
            'selectPayWay' => Configuration::get($this->name_upper . '_SHOW_PAYWAY'),
            'showPayWayLogo' => Configuration::get($this->name_upper . '_SHOW_PAYWAY_LOGO'),
            'showBaner' => Configuration::get($this->name_upper . '_SHOW_BANER'),
            'gateways' => $gateways,
            'regulations_get' => $this->context->link->getModuleLink('bluepayment', 'regulationsGet', [], true),
            'start_payment_translation' =>
                $this->l('Start payment'),
            'order_subject_to_payment_obligation_translation' =>
                $this->l('Order with the obligation to pay'),
        ]);

        $newOptions = [];

        $cart_id_time = $this->context->cart->id . '-' . time();

        if (!empty($gateways)) {
            $newOption = new PaymentOption();
            $newOption->setCallToActionText(
                Configuration::get($this->name_upper . '_PAYMENT_NAME', $this->context->language->id)
            )
                ->setAction($moduleLink)
                ->setInputs([
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway',
                        'value' => '0',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_cart_id',
                        'value' => $cart_id_time,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment-hidden-psd2-regulation-id',
                        'value' => '0',
                    ],
                ])
                ->setLogo($this->context->shop->getBaseURL(true) . 'modules/bluepayment/views/img/logo.png')
                ->setAdditionalInformation($this->fetch('module:bluepayment/views/templates/hook/payment.tpl'));

            $newOptions[] = $newOption;
        }

        if ($blik) {
            $blikGateway = new BlueGateway($blik);
            $blikModuleLink = $this->context->link->getModuleLink('bluepayment', 'chargeBlik', [], true);
            $this->smarty->assign([
                'blik_gateway' => $blikGateway,
                'blik_moduleLink' => $blikModuleLink,
            ]);
            $blikOption = new PaymentOption();
            $blikOption->setCallToActionText($blikGateway->gateway_name)
                ->setAction($blikModuleLink)
                ->setBinary(true)
                ->setLogo($blikGateway->gateway_logo_url)
                ->setForm($this->fetch('module:bluepayment/views/templates/hook/paymentBlik.tpl'));
            $newOptions[] = $blikOption;
        }

        /**
         * G-pay button will show only in secure enviroments, it mean:
         * 127.0.0.1, localhost, secure SSL host
         */
        if ($gpay) {
            $gpayGateway = new BlueGateway($gpay);
            $gpayMerchantInfo = $this->context->link->getModuleLink('bluepayment', 'merchantInfo', [], true);
            $gpay_moduleLinkCharge = $this->context->link->getModuleLink('bluepayment', 'chargeGPay', [], true);

            $this->smarty->assign([
                'gpay_merchantInfo' => $gpayMerchantInfo,
                'gpay_moduleLinkCharge' => $gpay_moduleLinkCharge,
            ]);
            $gpayOption = new PaymentOption();
            $gpayOption->setCallToActionText($gpayGateway->gateway_name)
                ->setAction($gpayMerchantInfo)
                ->setBinary(true)
                ->setLogo($gpayGateway->gateway_logo_url)
                ->setInputs([
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway',
                        'value' => 0,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'gpay_get_merchant_info',
                        'value' => $gpayMerchantInfo,
                    ]
                ])
                ->setAdditionalInformation($this->fetch('module:bluepayment/views/templates/hook/paymentGpay.tpl'));
            $newOptions[] = $gpayOption;
        }

        if ($smartney && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH) >= (float)SMARTNEY_MIN_AMOUNT && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH) <= (float)SMARTNEY_MAX_AMOUNT) {
            $smartneyGateway = new BlueGateway($smartney);
            $smartneyMerchantInfo = $this->context->link->getModuleLink('bluepayment', 'merchantInfo', [], true);
            $smartney_moduleLinkCharge = $this->context->link->getModuleLink('bluepayment', 'chargeSmartney', [], true);

            $this->smarty->assign([
                'smartney_merchantInfo' => $smartneyMerchantInfo,
                'smartney_moduleLinkCharge' => $smartney_moduleLinkCharge,
            ]);
            $smartneyOption = new PaymentOption();
            $smartneyOption->setCallToActionText($smartneyGateway->gateway_name)
                ->setAction($moduleLink)
                ->setLogo($smartneyGateway->gateway_logo_url)
                ->setInputs([
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway',
                        'value' => GatewayModel::GATEWAY_ID_SMARTNEY,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway_id',
                        'value' => GatewayModel::GATEWAY_ID_SMARTNEY,
                    ],
                ]);
            $newOptions[] = $smartneyOption;
        }

        if ($iframe
            && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH) >= (float)IFRAME_MIN_AMOUNT
        ) {
            $iframeGateway = new BlueGateway($iframe);
            $iframeOption = new PaymentOption();
            $iframeOption->setCallToActionText($iframeGateway->gateway_name)
                ->setAction($moduleLink)
                ->setInputs([
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway',
                        'value' => GatewayModel::GATEWAY_ID_IFRAME,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway_id',
                        'value' => GatewayModel::GATEWAY_ID_IFRAME,
                    ],
                ])
                ->setLogo($iframeGateway->gateway_logo_url);
            $newOptions[] = $iframeOption;
        }

        if ($cardGateway) {
            $card = new BlueGateway($cardGateway);
            $cardOption = new PaymentOption();
            $cardOption->setCallToActionText($card->gateway_name)
                ->setAction($moduleLink)
                ->setInputs([
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway',
                        'value' => GatewayModel::GATEWAY_ID_CARD,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_gateway_id',
                        'value' => GatewayModel::GATEWAY_ID_CARD,
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'bluepayment_cart_id',
                        'value' => $cart_id_time,
                    ],
                ])
                ->setLogo($card->gateway_logo_url);
            $newOptions[] = $cardOption;
        }

        return $newOptions;
    }

    /**
     * Generuje i zwraca klucz hash na podstawie wartości pól z tablicy
     *
     * @param array $data
     *
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        require_once dirname(__FILE__) . '/sdk/index.php';

        $values_array = array_values($data);
        $values_array_filter = array_filter($values_array);

        $comma_separated = implode(',', $values_array_filter);

        $replaced = str_replace(',', HASH_SEPARATOR, $comma_separated);

        return hash(Gateway::HASH_SHA256, $replaced);
    }

    /**
     * Hak do kroku płatności zwrotnej/potwierdzenia zamówienia
     *
     * @param $params
     *
     * @return bool|void
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        if (!isset($params['order']) || ($params['order']->module != $this->name)) {
            return false;
        }

        $currency = new Currency($params['order']->id_currency);

        $products = [];

        foreach ($params['order']->getProducts() as $product) {
            $cat = new Category($product['id_category_default'], $this->context->language->id);

            $newProduct = new stdClass();
            $newProduct->name = $product['product_name'];
            $newProduct->category = $cat->name;
            $newProduct->price = $product['price'];
            $newProduct->quantity = $product['product_quantity'];
            $newProduct->sku = $product['product_reference'];

            $products[] = $newProduct;
        }

        $this->context->smarty->assign([
            'order_id' => $params['order']->id,
            'shop_name' => $this->context->shop->name,
            'revenue' => $params['order']->total_paid,
            'shipping' => $params['order']->total_shipping,
            'tax' => $params['order']->carrier_tax_rate,
            'currency' => $currency->iso_code,
            'products' => $products,
        ]);

        return $this->fetch('module:bluepayment/views/templates/hook/paymentReturn.tpl');
    }

    public function hookOrderConfirmation($params)
    {
        $id_default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $order = new OrderCore($params['order']->id);
        $state = $order->getCurrentStateFull($id_default_lang);

        $orderStatusMessage = OrderStatusMessageDictionary::getMessage($state['id_order_state']) ?? $state['name'];

        $this->context->smarty->assign([
            'order_status' => $this->l($orderStatusMessage),
        ]);

        return $this->fetch('module:bluepayment/views/templates/hook/order-confirmation.tpl');
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     *
     * @param SimpleXMLElement $response
     *
     * @return bool
     */
    public function validAllTransaction($response)
    {
        require_once dirname(__FILE__) . '/sdk/index.php';

        $order = explode('-', $response->transactions->transaction->orderID)[0];
        $order = new OrderCore($order);
        $currency = new Currency($order->id_currency);
        $service_id = $this->parseConfigByCurrency($this->name_upper . '_SERVICE_PARTNER_ID', $currency->iso_code);
        $shared_key = $this->parseConfigByCurrency($this->name_upper . '_SHARED_KEY', $currency->iso_code);

        if ($service_id != $response->serviceID) {
            return false;
        }

        $this->checkHashArray = [];
        $hash = (string)$response->hash;
        $this->checkHashArray[] = (string)$response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->checkInList($trans);
        }
        $this->checkHashArray[] = $shared_key;
        $localHash = hash(Gateway::HASH_SHA256, implode(HASH_SEPARATOR, $this->checkHashArray));

        return $localHash === $hash;
    }

    private function checkInList($list)
    {
        foreach ((array)$list as $row) {
            if (is_object($row)) {
                $this->checkInList($row);
            } else {
                $this->checkHashArray[] = $row;
            }
        }
    }

    /**
     * Haczyk dla nagłówków stron
     */
    public function hookHeader()
    {
        Media::addJsDef(
            [
                'bluepayment_env' => (int)Configuration::get($this->name_upper . '_TEST_ENV') === 1 ?
                    'TEST' : 'PRODUCTION'
            ]
        );

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addJS($this->_path . 'views/js/blik_v3.js');
        $this->context->controller->addJS($this->_path . 'views/js/gpay.js');
    }

    /**
     * @param $realOrderId
     * @param $order_id
     * @param $confirmation
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function returnConfirmation($realOrderId, $order_id, $confirmation)
    {
        if (null === $order_id) {
            $order_id = explode('-', $realOrderId)[0];
        }

        $order = new Order($order_id);
        $currency = new Currency($order->id_currency);
        // Id serwisu partnera
        $service_id = $this->parseConfigByCurrency(
            $this->name_upper . '_SERVICE_PARTNER_ID',
            $currency->iso_code
        );

        // Klucz współdzielony
        $shared_key = $this->parseConfigByCurrency($this->name_upper . '_SHARED_KEY', $currency->iso_code);

        // Tablica danych z których wygenerować hash
        $hash_data = [$service_id, $realOrderId, $confirmation, $shared_key];

        // Klucz hash
        $hash_confirmation = $this->generateAndReturnHash($hash_data);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $confirmation_list = $dom->createElement('confirmationList');

        $dom_service_id = $dom->createElement('serviceID', $service_id);
        $confirmation_list->appendChild($dom_service_id);

        $transactions_confirmations = $dom->createElement('transactionsConfirmations');
        $confirmation_list->appendChild($transactions_confirmations);

        $dom_transaction_confirmed = $dom->createElement('transactionConfirmed');
        $transactions_confirmations->appendChild($dom_transaction_confirmed);

        $dom_order_id = $dom->createElement('orderID', $realOrderId);
        $dom_transaction_confirmed->appendChild($dom_order_id);

        $dom_confirmation = $dom->createElement('confirmation', $confirmation);
        $dom_transaction_confirmed->appendChild($dom_confirmation);

        $dom_hash = $dom->createElement('hash', $hash_confirmation);
        $confirmation_list->appendChild($dom_hash);

        $dom->appendChild($confirmation_list);

        echo $dom->saveXML();
    }


    /**
     * Odczytuje dane oraz sprawdza zgodność danych o transakcji/płatności
     * zgodnie z uzyskaną informacją z kontrolera 'StatusModuleFront'
     *
     * @param $response
     *
     * @throws Exception
     */
    public function processStatusPayment($response)
    {

        $transaction_xml = $response->transactions->transaction;

        if ($this->validAllTransaction($response)) {
            // Aktualizacja statusu zamówienia i transakcji
            $this->updateStatusTransactionAndOrder($transaction_xml);
        } else {
            $message = $this->name_upper . ' - Invalid hash: ' . $response->hash;
            // Potwierdzenie zwrotne o transakcji nie autentycznej
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'Order', $transaction_xml->orderID);
            $this->returnConfirmation($transaction_xml->orderID, null, self::TRANSACTION_NOTCONFIRMED);
        }
    }

    /**
     * Sprawdza czy zamówienie zostało anulowane
     *
     * @param object $order
     *
     * @return boolean
     */
    public function isOrderCompleted($order)
    {
        $status = $order->getCurrentState();
        $stateOrderTab = [Configuration::get('PS_OS_CANCELED')];

        return in_array($status, $stateOrderTab);
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     *
     * @throws Exception
     */
    protected function updateStatusTransactionAndOrder($transaction)
    {

        require_once dirname(__FILE__) . '/sdk/index.php';

        // Identyfikatory statusów płatności

        $status_accept_pay_id = Configuration::get($this->name_upper . '_STATUS_ACCEPT_PAY_ID');
        $status_waiting_pay_id = Configuration::get($this->name_upper . '_STATUS_WAIT_PAY_ID');
        $status_error_pay_id = Configuration::get($this->name_upper . '_STATUS_ERROR_PAY_ID');

        // Status płatności
        $payment_status = (string)$transaction->paymentStatus;

        // Id transakcji nadany przez bramkę
        $remote_id = (string)$transaction->remoteID;

        // Id zamówienia
        $realOrderId = (string)$transaction->orderID;
        $order_id = explode('-', $realOrderId)[0];

        // Objekt zamówienia
        $order = new OrderCore($order_id);

        // Obiekt płatności zamówienia
        $order_payments = $order->getOrderPaymentCollection();

        if (count($order_payments) > 0) {
            $order_payment = $order_payments[0];
        } else {
            $order_payment = new OrderPaymentCore();
        }

        if (!Validate::isLoadedObject($order)) {
            $message = $this->name_upper . ' - Order not found';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'Order', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);

            return;
        }

        if (!is_object($order_payment)) {
            $message = $this->name_upper . ' - Order payment not found';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'OrderPayment', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);

            return;
        }

        $transactionData = [
            'remote_id' => (string)$transaction->remoteID,
            'amount' => (string)$transaction->amount,
            'currency' => (string)$transaction->currency,
            'gateway_id' => (string)$transaction->gatewayID,
            'payment_date' => date('Y-m-d H:i:s', strtotime($transaction->paymentDate)),
            'payment_status' => (string)$transaction->paymentStatus,
            'payment_status_details' => (string)$transaction->paymentStatusDetails,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Db::getInstance()->update('blue_transactions', $transactionData, 'order_id = \'' . pSQL($realOrderId) . '\'');

        // Suma zamówienia
        $total_paid = $order->total_paid;
        $amount = number_format(round($total_paid, 2), 2, '.', '');
        // Jeśli zamówienie jest otwarte i status zamówienia jest różny od pustej wartości
        if (!$this->isOrderCompleted($order) && $payment_status != '') {
            switch ($payment_status) {
                // Jeśli transakcja została rozpoczęta
                case self::PAYMENT_STATUS_PENDING:
                    // Jeśli aktualny status zamówienia jest różny od ustawionego jako "oczekiwanie na płatność"
                    if ($order->current_state != $status_waiting_pay_id) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->changeIdOrderState($status_waiting_pay_id, $order_id);
                        $new_history->addWithemail(true);
                    }
                    break;
                // Jeśli transakcja została zakończona poprawnie
                case self::PAYMENT_STATUS_SUCCESS:
                    if ($order->current_state == $status_waiting_pay_id ||
                        $order->current_state == $status_error_pay_id
                    ) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->changeIdOrderState($status_accept_pay_id, $order_id);
                        $new_history->addWithemail(true);
                        if ((string)$transaction->gatewayID == (string)GatewayModel::GATEWAY_ID_BLIK) {
                            $transactionData['blik_status'] = (string)$transaction->paymentStatus;
                            Db::getInstance()->update(
                                'blue_transactions',
                                $transactionData,
                                'order_id = \'' . pSQL($realOrderId) . '\''
                            );
                        }

                        if (is_object($order_payment)) {
                            $order_payment = $order->getOrderPayments()[0];
                            $order_payment->amount = $amount;
                            $order_payment->transaction_id = $remote_id;
                            $order_payment->update();
                        }
                    }
                    break;
                // Jeśli transakcja nie została zakończona poprawnie
                case self::PAYMENT_STATUS_FAILURE:
                    // Jeśli aktualny status zamówienia jest równy ustawionemu jako "oczekiwanie na płatność"
                    if ($order->current_state == $status_waiting_pay_id) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->changeIdOrderState($status_error_pay_id, $order_id);
                        $new_history->addWithemail(true);
                    }
                    break;
                default:
                    break;
            }
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_CONFIRMED);
        } else {
            $message = $this->name_upper . ' - Order status is cancel or payment status unknown';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'OrderState', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, $message);
        }
    }

    public function installConfigurationTranslations()
    {
        $name_langs = [];
        $name_langs_extra = [];
        //@TODO: po zmianie tekstu na klucze do tłumaczeń pobierać nazwę i opis poprzez klucze
        foreach (Language::getLanguages() as $lang) {
            if ($lang['locale'] === "pl-PL") {
                $name_langs[$lang['id_lang']] =
                    $this->trans('Zapłać przez BlueMedia', [], 'Modules.Bluepayment', $lang['locale']);
                $name_langs_extra[$lang['id_lang']] =
                    $this->trans('Po zamówieniu przekieruje cię do systemu płatności BlueMedia', [], 'Modules.Bluepayment', $lang['locale']);
            } else {
                $name_langs[$lang['id_lang']] =
                    $this->trans('Pay via BlueMedia', [], 'Modules.Bluepayment', $lang['locale']);
                $name_langs_extra[$lang['id_lang']] =
                    $this->trans('After order redirect to BlueMedia payment system', [], 'Modules.Bluepayment', $lang['locale']);
            }
        }

        Configuration::updateValue($this->name_upper . '_PAYMENT_NAME', $name_langs);
        Configuration::updateValue($this->name_upper . '_PAYMENT_NAME_EXTRA', $name_langs_extra);

        return true;
    }
}

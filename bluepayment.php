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

require dirname(__FILE__).'/vendor/autoload.php';

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
            'displayBackOfficeHeader',
            'displayAdminAfterHeader',
            'adminOrder',
            'adminPayments'
        ];
    public $id_order = null;
    public $bm_order_id = '';

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

        require_once dirname(__FILE__).'/config/config.inc.php';

        $this->tab = 'payments_gateways';
        $this->version = '2.7.0';
        $this->author = 'Blue Media S.A.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->module_key = '7dac119ed21c46a88632206f73fa4104';
        $this->images_dir = _MODULE_DIR_.'bluepayment/views/img/';

        parent::__construct();

        $this->displayName = $this->l('Blue Media payments');
        $this->description = $this->l(
            'Plugin supports online payments implemented by payment gateway Blue Media company.'
        );
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
            Configuration::updateValue($this->name_upper.'_TEST_ENV', 1);
            Configuration::updateValue($this->name_upper.'_SHOW_PAYWAY', 1);
            Configuration::updateValue($this->name_upper.'_SHOW_PAYWAY_LOGO', 1);
            Configuration::updateValue($this->name_upper.'_SHOW_BANER', 0);
            Configuration::updateValue($this->name_upper.'_PAYMENT_NAME', 'Pay via Blue Media');
            Configuration::updateValue($this->name_upper.'_PAYMENT_GROUP_NAME', 'Przelew internetowy');

            return true;
        }

        return false;
    }

    public function hookDisplayAdminAfterHeader()
    {

        try {
            // Łączenie z API Prestashop addons
            $api_url = 'https://api-addons.prestashop.com/';
            $params = '?format=json&iso_lang=pl&iso_code=pl&method=module&id_module=49791&method=listing&action=module';

            $api_request = $api_url.$params;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $api_request);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $output = curl_exec($curl);
            curl_close($curl);

            $api_response = json_decode($output);
            $ver = $api_response->modules[0]->version;

            if ($ver && version_compare($ver, $this->version, '>')) {
                return $this->context->smarty->fetch(
                    _PS_MODULE_DIR_.$this->name.'/views/templates/admin/_partials/upgrade.tpl'
                );
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Brak aktualizacji', 1);
        }

        return null;
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

            Configuration::deleteByName($this->name_upper.'_SHARED_KEY');
            Configuration::deleteByName($this->name_upper.'_SERVICE_PARTNER_ID');

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
            $state = true;
            $tabparent = "AdminBluepaymentPayments";
            $id_parent = (int)Tab::getIdFromClassName($tabparent);

            if ($id_parent == 0) {
                $tab = new Tab();
                $tab->active = 1;
                $tab->class_name = "AdminBluepaymentPayments";
                $tab->visible = true;
                $tab->name = [];
                $tab->id_parent = -1;

                foreach (Language::getLanguages(true) as $lang) {
                    if ($lang['locale'] === "pl-PL") {
                        $tab->name[$lang['id_lang']] =
                            $this->trans('Blue Media - Konfiguracja', [], 'Modules.Bluepayment', $lang['locale']);
                    } else {
                        $tab->name[$lang['id_lang']] =
                            $this->trans('Blue Media - Configuration', [], 'Modules.Bluepayment', $lang['locale']);
                    }
                }

                $tab->id_parent = -1;
                $tab->module = $this->name;
                $state &= $tab->add();
                $id_parent = $tab->id;
            }

            $sub_tabs = [
                [
                    'class' => 'AdminBluepaymentAjax',
                    'name' => 'Bluepayment Ajax',
                    'parrent' => -1
                ],
            ];

            foreach ($sub_tabs as $sub_tab) {
                $idtab = (int)Tab::getIdFromClassName($sub_tab['class']);
                if ($idtab == 0) {
                    $tab = new Tab();
                    $tab->active = 1;
                    $tab->class_name = $sub_tab['class'];
                    $tab->name = [];
                    foreach (Language::getLanguages() as $lang) {
                        $tab->name[$lang["id_lang"]] = $sub_tab['name'];
                    }
                    if (isset($sub_tab['parrent'])) {
                        $tab->id_parent = (int)$sub_tab['parrent'];
                    } else {
                        $tab->id_parent = $id_parent;
                    }

                    $tab->module = $this->name;
                    $state &= $tab->add();
                }
            }

            return (bool)$state;
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

        $id_tabs = [
            'AdminBluepayment',
            'AdminBluepaymentPayments',
            'AdminBluepaymentAjax',
        ];

        foreach ($id_tabs as $id_tab) {
            $idtab = (int)Tab::getIdFromClassName($id_tab);
            $tab = new Tab((int)$idtab);
            if (Validate::isLoadedObject($tab)) {
                $parentTabID = $tab->id_parent;
                $tab->delete();
                $tabCount = Tab::getNbTabs((int)$parentTabID);
                if ($tabCount == 0) {
                    $parentTab = new Tab((int)$parentTabID);
                    $parentTab->delete();
                }
            }
        }
        return true;
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
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminBluepaymentPayments')
        );
    }

    public function getPathUri()
    {
        return $this->_path;
    }

    public function installDb()
    {
        require_once _PS_MODULE_DIR_.$this->name.'/sql/install.php';
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
            require_once _PS_MODULE_DIR_.$this->name.'/sql/uninstall.php';
            $this->removeOrderStatuses();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - The table cannot be deleted from the database', 4);
        }
    }

    public function getGatewaysListFields()
    {
        return [
            'position' => [
                'title' => $this->l('Position'),
                'position' => 'position',
                'ajax' => true,
                'align' => 'center',
                'orderby' => false,
            ],
            'gateway_logo_url' => [
                'title' => $this->l('Payment method'),
                'callback' => 'displayGatewayLogo',
                'callback_object' => Module::getInstanceByName($this->name),
                'orderby' => false,
                'search' => false,
            ],
            'gateway_name' => [
                'title' => '',
                'orderby' => false,
            ],
            'gateway_payments' => [
                'title' => '',
                'callback' => 'displayGatewayPayments',
                'callback_object' => Module::getInstanceByName($this->name),
                'orderby' => false,
            ],
        ];
    }

    public function getListChannels($currency)
    {
        $gateway = Db::getInstance((bool)_PS_USE_SQL_SLAVE_)->executeS('SELECT id_blue_gateway_channels, 
            gateway_payments, gateway_id, gateway_name, gateway_logo_url, gateway_type, position, bank_name, 
            gateway_currency, gateway_status, 
            position FROM `'._DB_PREFIX_.'blue_gateway_channels` 
            WHERE gateway_currency = "'.pSql($currency).'" ORDER BY position');

        return $gateway;
    }

    public function getListAllPayments($currency = 'PLN', $type = null)
    {

        $q = '';
        if ($type === 'wallet') {
            $q = 'IN ("Apple Pay","Google Pay")';
        } elseif ($type === 'transfer') {
            $q = 'NOT IN ("BLIK","Apple Pay","Google Pay","PBC płatność testowa","Kup teraz, zapłać później","Alior Raty")';
        }

        $gateway = Db::getInstance((bool)_PS_USE_SQL_SLAVE_)->executeS('SELECT id, gateway_id, 
        gateway_name, gateway_logo_url, gateway_type, position, bank_name, gateway_currency, gateway_status, 
        position FROM `'._DB_PREFIX_.'blue_gateways` 
        WHERE gateway_name '.$q.' 
        AND gateway_currency = "'.pSql($currency).'" 
        ORDER BY position');

        return $gateway;
    }

    /**
     * Pobieranie metod płatności w administracji
     */

    public function hookAdminPayments()
    {
        $list = [];
        $transfer_payments = [];
        $wallets = [];

        foreach ($this->getSortCurrencies() as $currency) {
            /// Tworzy grupę w backoffice
            $paymentList = $this->getListChannels($currency['iso_code']);
            $title = $currency['name'].' ('.$currency['iso_code'].')';

            if (!empty($paymentList)) {
                $list[] = $this->renderAdditionalOptionsList($paymentList, $title);
            }

            /// Pobiera kanały do grup
            if ($this->getListAllPayments($currency['iso_code'], 'transfer')) {
                $transfer_payments[$currency['iso_code']] = $this->getListAllPayments(
                    $currency['iso_code'],
                    'transfer'
                );
            }

            if ($this->getListAllPayments($currency['iso_code'], 'transfer')) {
                $wallets[$currency['iso_code']] = $this->getListAllPayments(
                    $currency['iso_code'],
                    'wallet'
                );
            }
        }

        $this->context->smarty->assign(
            [
                'list' => $list,
                'transfer_payments' => $transfer_payments,
                'wallets' => $wallets
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/_configure/helpers/container_list.tpl');
    }

    /**
     * Sortowanie walut po id
     *
     * @return array
     */

    public function getSortCurrencies()
    :array
    {
        $sortCurrencies = Currency::getCurrencies();

        usort($sortCurrencies, function ($a, $b) {
            if ($a['id'] == $b['id']) {
                return 0;
            }
            return $a['id'] > $b['id'] ? 1 : -1;
        });
        return (array)$sortCurrencies;
    }

    /**
     * Pobranie kodów iso dostępnych walut
     *
     * @return array
     */

    public function getIsoCodeCurrencies()
    :array
    {

        $sortCurrencies = Currency::getCurrencies();

        return (array)$sortCurrencies;
    }

    public function displayGatewayLogo($gatewayLogo)
    {
        return '<img width="65" class="img-fluid" src="'.$gatewayLogo.'" />';
    }

    public function displayGatewayPayments($gatewayLogo, $object)
    {
        if ($gatewayLogo == 1) {
            return '<div class="btn-info" data-toggle="modal" data-target="#'.str_replace(
                ' ',
                '_',
                $object['gateway_name']
            ).'_'.$object['gateway_currency'].'">
            <img class="img-fluid" width="24" src="'.$this->images_dir.'question.png"></div>';
        } else {
            return '';
        }
    }

    protected function renderAdditionalOptionsList($payments, $title)
    {
        $helper = new HelperList();
        $helper->table = 'blue_gateway_channels';
        $helper->name_controller = $this->name;
        $helper->module = $this;
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_blue_gateway_channels';
        $helper->no_link = true;
        $helper->title = $title;
        $helper->currentIndex = AdminController::$currentIndex;
        $content = $payments;
        $helper->token = Tools::getAdminTokenLite('AdminBluepaymentPayments');
        $helper->position_identifier = 'position';
        $helper->orderBy = 'position';
        $helper->orderWay = 'ASC';
        $helper->show_toolbar = false;

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
            $data[$this->name_upper.'_PAYMENT_NAME'][$lang['id_lang']] =
                Configuration::get($this->name_upper.'_PAYMENT_NAME', $lang['id_lang']);
            $data[$this->name_upper.'_PAYMENT_GROUP_NAME'][$lang['id_lang']] =
                Configuration::get($this->name_upper.'_PAYMENT_GROUP_NAME', $lang['id_lang']);
        }

        foreach ($this->getSortCurrencies() as $currency) {
            $data[$this->name_upper.'_SERVICE_PARTNER_ID_'.$currency['iso_code']] =
                $this->parseConfigByCurrency($this->name_upper.'_SERVICE_PARTNER_ID', $currency['iso_code']);
            $data[$this->name_upper.'_SHARED_KEY_'.$currency['iso_code']] =
                $this->parseConfigByCurrency($this->name_upper.'_SHARED_KEY', $currency['iso_code']);
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
            $this->name_upper.'_STATUS_WAIT_PAY_ID',
            $this->name_upper.'_STATUS_ACCEPT_PAY_ID',
            $this->name_upper.'_STATUS_ERROR_PAY_ID',
            $this->name_upper.'_PAYMENT_NAME',
            $this->name_upper.'_PAYMENT_GROUP_NAME',
            $this->name_upper.'_SHOW_PAYWAY',
            $this->name_upper.'_SHOW_PAYWAY_LOGO',
            $this->name_upper.'_SHOW_BANER',
            $this->name_upper.'_TEST_ENV',
        ];
    }

    /**
     * @throws PrestaShopDatabaseException
     * @return null|string
     */
    public function hookAdminOrder($params)
    {
        $this->id_order = $params['id_order'];
        $order = new Order($this->id_order);

        $output = '';

        if ($order->module !== 'bluepayment') {
            return $output;
        }
        $updateOrderStatusMessage = '';

        $order_payment = $this->getLastOrderPaymentByOrderId($params['id_order']);

        $refundable = $order_payment['payment_status'] === self::PAYMENT_STATUS_SUCCESS;

        $refund_type = Tools::getValue('bm_refund_type', 'full');
        $refund_amount = $refund_type === 'full'
            ? $order->total_paid
            : (float)str_replace(',', '.', Tools::getValue('bm_refund_amount'));
        $refund_errors = [];
        $refund_success = [];

        if ($refundable && Tools::getValue('go-to-refund-bm')) {
            if ($refund_amount > $order->total_paid) {
                $refund_errors[] = $this->l('The refund amount you entered is greater than paid amount.');
            } else {
                $refund = $this->bmOrderRefund(
                    $refund_amount,
                    $order_payment['remote_id'],
                    $order->id
                );

                if (!empty($refund[1])) {
                    if ($refund[0] !== true) {
                        $refund_errors[] = $this->l('Refund error: ').$refund[1];
                    }
                }

                if (empty($refund_errors) && $refund[0] === true) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->id_employee = (int)$this->context->employee->id;
                    $history->changeIdOrderState(Configuration::get('PS_OS_REFUND'), (int)$order->id);
                    $history->addWithemail(true, []);
                    $refund_success[] = $this->l('Successful refund');

                    //Tools::redirectAdmin('index.php?tab=AdminOrders&id_order=' . (int)$order->id . '&vieworder' . '
                    //&token=' . Tools::getAdminTokenLite('AdminOrders'));
                }
            }
        }

        $this->context->smarty->assign([
            'BM_ORDERS' => $this->getOrdersByOrderId($params['id_order']),
            'BM_ORDER_ID' => $this->id_order,
            'BM_CANCEL_ORDER_MESSAGE' => $updateOrderStatusMessage,
            'SHOW_REFUND' => $refundable,
            'REFUND_FULL_AMOUNT' => number_format($order->total_paid, 2, '.', ''),
            'REFUND_ERRORS' => $refund_errors,
            'REFUND_SUCCESS' => $refund_success,
            'REFUND_TYPE' => $refund_type,
            'REFUND_AMOUNT' => $refund_amount,
        ]);

        return $this->fetch('module:bluepayment/views/templates/admin/status.tpl');
        //        return $this->setTemplate('/views/templates/admin/status.tpl');
    }

    private function bmOrderRefund($amount, $remote_id, $id_order)
    {
        $amount = number_format($amount, 2, '.', '');
        $order = new OrderCore($id_order);
        $currency = new Currency($order->id_currency);
        $service_id = $this->parseConfigByCurrency(
            $this->name_upper.'_SERVICE_PARTNER_ID',
            $currency->iso_code
        );
        $shared_key = $this->parseConfigByCurrency($this->name_upper.'_SHARED_KEY', $currency->iso_code);
        $message_id = $this->randomString(32);
        // Tablica danych z których wygenerować hash

        $hash_data = [$service_id, $message_id, $remote_id, $amount, $currency->iso_code, $shared_key];
        // Klucz hash
        $hash_confirmation = $this->generateAndReturnHash($hash_data);

        $curl = curl_init();
        $postfields = 'ServiceID='.$service_id.
            '&MessageID='.$message_id.
            '&RemoteID='.$remote_id.
            '&Amount='.$amount.
            '&Currency='.$currency->iso_code.
            '&Hash='.$hash_confirmation;

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://pay-accept.bm.pl/transactionRefund',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $result_success = false;
        $info = false;
        if ($xml->messageID) {
            if ($xml->messageID == $message_id) {
                $result_success = true;
            }
        } else {
            $info = $xml->description;
        }
        return [$result_success, $info];
    }

    public function randomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, Tools::strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * @param $id_order
     *
     * @return bool | array
     */
    private function getLastOrderPaymentByOrderId($id_order)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'blue_transactions
			WHERE order_id like "'.pSQL($id_order).'-%"
			ORDER BY created_at DESC';

        $result = Db::getInstance()->getRow($sql, false);

        return $result ? $result : false;
    }

    /**
     * @param $id_order
     *
     * @throws PrestaShopDatabaseException
     * @return bool | array
     */
    private function getOrdersByOrderId($id_order)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'blue_transactions
			WHERE order_id like "'.pSQL($id_order).'-%"
			ORDER BY created_at DESC';

        $result = Db::getInstance()->executeS($sql, true, false);

        return $result ? $result : false;
    }

    /**
     * Tworzenie metod płatności
     */

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return null;
        }

        $currency = $this->context->currency;

        $serviceId = $this->parseConfigByCurrency($this->name_upper.'_SERVICE_PARTNER_ID', $currency->iso_code);
        $sharedKey = $this->parseConfigByCurrency($this->name_upper.'_SHARED_KEY', $currency->iso_code);
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

        require_once dirname(__FILE__).'/sdk/index.php';

        /**
         * Pobiera wszystkie kanały płatności dla przelewów internetowych
         */

        $gateways = new PrestaShopCollection('BlueGateway', $this->context->language->id);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_BLIK);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_IFRAME);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_CARD);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_GOOGLE_PAY);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_APPLE_PAY);
        $gateways->where('gateway_id', '!=', GatewayModel::GATEWAY_ID_SMARTNEY);
        $gateways->where('gateway_status', '=', 1);
        $gateways->where('gateway_currency', '=', $currency->iso_code);
        $gateways->orderBy('position');
        $gateways = $gateways->getResults();

        $cart_id_time = $this->context->cart->id.'-'.time();

        $this->smarty->assign([
            'module_link' => $moduleLink,
            'ps_version' => _PS_VERSION_,
            'module_dir' => $this->_path,
            'payment_name' => Configuration::get($this->name_upper.'_PAYMENT_NAME', $this->context->language->id),
            'payment_group_name' =>
                Configuration::get($this->name_upper.'_PAYMENT_GROUP_NAME', $this->context->language->id),
            'selectPayWay' => Configuration::get($this->name_upper.'_SHOW_PAYWAY'),
            'showPayWayLogo' => Configuration::get($this->name_upper.'_SHOW_PAYWAY_LOGO'),
            'showBaner' => Configuration::get($this->name_upper.'_SHOW_BANER'),
            'gateways' => $gateways,
            'regulations_get' => $this->context->link->getModuleLink('bluepayment', 'regulationsGet', [], true),
            'start_payment_translation' => $this->l('Start payment'),
            'start_payment_intro' => $this->l('Internet transfer, BLIK, payment card, Google Pay, Apple Pay'),
            'order_subject_to_payment_obligation_translation' => $this->l('Order with the obligation to pay'),
        ]);

        $newOptions = [];

        if (Configuration::get($this->name_upper.'_SHOW_PAYWAY')) {

            /**
             * Tworzenie grupy płatności
             */

            $blik = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_BLIK, $currency->iso_code);
            $cardGateway = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_CARD, $currency->iso_code);
            $gpay = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_GOOGLE_PAY, $currency->iso_code);
            $smartney = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_SMARTNEY, $currency->iso_code);
            $applePay = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_APPLE_PAY, $currency->iso_code);
            $iframe = BlueGateway::gatewayIsActive(GatewayModel::GATEWAY_ID_IFRAME, $currency->iso_code);

            /**
             * Inne bramki
             */

            $payment_group = new PrestaShopCollection('BlueGatewayChannels', $this->context->language->id);
            $payment_group->where('gateway_status', '=', 1);
            $payment_group->where('gateway_currency', '=', $currency->iso_code);
            $payment_group->orderBy('position');
            $payment_group = $payment_group->getResults();

            if (!empty($payment_group)) {
                foreach ($payment_group as $p_group) {
                    if ($p_group->gateway_name === 'Przelew internetowy') {
                        $paymentName = Configuration::get(
                            $this->name_upper.'_PAYMENT_GROUP_NAME',
                            $this->context->language->id
                        );

                        if (!empty($gateways)) {
                            $newOption = new PaymentOption();
                            $newOption->setCallToActionText(
                                $paymentName
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
                                ->setLogo(
                                    $this->context->shop
                                        ->getBaseURL(true).'modules/bluepayment/views/img/blue-media.svg'
                                )->setAdditionalInformation(
                                    $this->fetch('module:bluepayment/views/templates/hook/payment.tpl')
                                );

                            $newOptions[] = $newOption;
                        }
                    }

                    if ($p_group->gateway_name === 'PBC płatność testowa') {
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
                    }

                    if ($p_group->gateway_name === 'BLIK') {
                        if ($blik) {
                            $blikGateway = new BlueGateway($blik);
                            $blikModuleLink = $this->context->link->getModuleLink(
                                'bluepayment',
                                'chargeBlik',
                                [],
                                true
                            );
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
                    }

                    if ($p_group->gateway_name === 'Wirtualny portfel') {
                        /**
                         * G-pay button will show only in secure enviroments, it mean:
                         * 127.0.0.1, localhost, secure SSL host
                         */

                        if ($gpay) {
                            $gpayGateway = new BlueGateway($gpay);
                            $gpayMerchantInfo = $this->context->link->getModuleLink(
                                'bluepayment',
                                'merchantInfo',
                                [],
                                true
                            );
                            $gpay_moduleLinkCharge = $this->context->link->getModuleLink(
                                'bluepayment',
                                'chargeGPay',
                                [],
                                true
                            );

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
                                ->setAdditionalInformation(
                                    $this->fetch('module:bluepayment/views/templates/hook/paymentGpay.tpl')
                                );
                            $newOptions[] = $gpayOption;
                        }

                        /// ApplePay

                        if ($applePay) {
                            $applePayGateway = new BlueGateway($applePay);
                            $applePayOption = new PaymentOption();
                            $applePayOption->setCallToActionText($applePayGateway->gateway_name)
                                ->setAction($moduleLink)
                                ->setLogo($applePayGateway->gateway_logo_url)
                                ->setInputs([
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway',
                                        'value' => GatewayModel::GATEWAY_ID_APPLE_PAY,
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway_id',
                                        'value' => GatewayModel::GATEWAY_ID_APPLE_PAY,
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_cart_id',
                                        'value' => $cart_id_time,
                                    ],

                                ]);
                            $newOptions[] = $applePayOption;
                        }
                    }

                    if ($p_group->gateway_name === 'Kup teraz, zapłać później') {
                        if ($smartney
                            && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                            >= (float)SMARTNEY_MIN_AMOUNT
                            && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                            <= (float)SMARTNEY_MAX_AMOUNT
                        ) {
                            $smartneyGateway = new BlueGateway($smartney);
                            $smartneyMerchantInfo = $this->context->link->getModuleLink(
                                'bluepayment',
                                'merchantInfo',
                                [],
                                true
                            );
                            $smartney_moduleLinkCharge = $this->context->link->getModuleLink(
                                'bluepayment',
                                'chargeSmartney',
                                [],
                                true
                            );

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
                }
            }
        } else {
            /**
             * Tworzenie przekierowania dla wszystkich płatności
             */

            $paymentName = Configuration::get($this->name_upper.'_PAYMENT_NAME', $this->context->language->id);

            $newOption = new PaymentOption();
            $newOption->setCallToActionText(
                $paymentName
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
                ->setLogo($this->context->shop->getBaseURL(true).'modules/bluepayment/views/img/blue-media.svg')
                ->setAdditionalInformation($this->fetch('module:bluepayment/views/templates/hook/payment.tpl'));

            $newOptions[] = $newOption;
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
        require_once dirname(__FILE__).'/sdk/index.php';

        $values_array = array_values($data);
        $values_array_filter = array_filter($values_array);

        $comma_separated = implode(',', $values_array_filter);

        $replaced = str_replace(',', HASH_SEPARATOR, $comma_separated);
        Configuration::updateValue(
            $this->name_upper.'_'.time(),
            $replaced.'|||'.hash(Gateway::HASH_SHA256, $replaced)
        );
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
        require_once dirname(__FILE__).'/sdk/index.php';

        $order = explode('-', $response->transactions->transaction->orderID)[0];

        $order = new OrderCore($order);
        $currency = new Currency($order->id_currency);

        $service_id = $this->parseConfigByCurrency($this->name_upper.'_SERVICE_PARTNER_ID', $currency->iso_code);
        $shared_key = $this->parseConfigByCurrency($this->name_upper.'_SHARED_KEY', $currency->iso_code);

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
                'bluepayment_env' => (int)Configuration::get($this->name_upper.'_TEST_ENV') === 1 ?
                    'TEST' : 'PRODUCTION'
            ]
        );

        $this->context->controller->addCSS($this->_path.'views/css/front/front.css');
        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addJS($this->_path.'views/js/blik_v3.js');
        $this->context->controller->addJS($this->_path.'views/js/gpay.js');
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
            $this->name_upper.'_SERVICE_PARTNER_ID',
            $currency->iso_code
        );

        // Klucz współdzielony
        $shared_key = $this->parseConfigByCurrency($this->name_upper.'_SHARED_KEY', $currency->iso_code);

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
        //        $this->debug($response);

        if ($this->validAllTransaction($response)) {
            // Aktualizacja statusu zamówienia i transakcji
            $this->updateStatusTransactionAndOrder($transaction_xml);
        } else {
            $message = $this->name_upper.' - Invalid hash: '.$response->hash;
            // Potwierdzenie zwrotne o transakcji nie autentycznej
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'Order', $transaction_xml->orderID);
            $this->returnConfirmation(
                $transaction_xml->orderID,
                null,
                self::TRANSACTION_NOTCONFIRMED
            );
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

        require_once dirname(__FILE__).'/sdk/index.php';

        // Identyfikatory statusów płatności

        $status_accept_pay_id = Configuration::get($this->name_upper.'_STATUS_ACCEPT_PAY_ID');
        $status_waiting_pay_id = Configuration::get($this->name_upper.'_STATUS_WAIT_PAY_ID');
        $status_error_pay_id = Configuration::get($this->name_upper.'_STATUS_ERROR_PAY_ID');
        //        $status_refund_pay_id = Configuration::get($this->name_upper . '_STATUS_REFUND_PAY_ID');

        // Status płatności
        $payment_status = pSql((string)$transaction->paymentStatus);

        // Id transakcji nadany przez bramkę
        $remote_id = pSql((string)$transaction->remoteID);

        // Id zamówienia
        $realOrderId = pSql((string)$transaction->orderID);
        $order_id = explode('-', $realOrderId)[0];

        // Objekt zamówienia
        $order = new OrderCore($order_id);
        //        $order = new OrderCore(81);
        // Obiekt płatności zamówienia
        $order_payments = $order->getOrderPaymentCollection();

        if (count($order_payments) > 0) {
            $order_payment = $order_payments[0];
        } else {
            $order_payment = new OrderPaymentCore();
        }

        if (!Validate::isLoadedObject($order)) {
            $message = $this->name_upper.' - Order not found';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'Order', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);

            return;
        }

        if (!is_object($order_payment)) {
            $message = $this->name_upper.' - Order payment not found';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'OrderPayment', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, self::TRANSACTION_NOTCONFIRMED);

            return;
        }

        $transactionData = [
            'remote_id' => pSql((string)$transaction->remoteID),
            'amount' => pSql((string)$transaction->amount),
            'currency' => pSql((string)$transaction->currency),
            'gateway_id' => pSql((string)$transaction->gatewayID),
            'payment_date' => date('Y-m-d H:i:s', strtotime($transaction->paymentDate)),
            'payment_status' => pSql((string)$transaction->paymentStatus),
            'payment_status_details' => pSql((string)$transaction->paymentStatusDetails),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Db::getInstance()->update('blue_transactions', $transactionData, 'order_id = \''.pSQL($realOrderId).'\'');

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
                                'order_id = \''.pSQL($realOrderId).'\''
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
            $message = $this->name_upper.' - Order status is cancel or payment status unknown';
            PrestaShopLogger::addLog('BM - '.$message, 3, null, 'OrderState', $order_id);
            $this->returnConfirmation($realOrderId, $order_id, $message);
        }
    }

    public function installConfigurationTranslations()
    {
        $name_langs = [];
        $name_langs_group = [];

        //@TODO: po zmianie tekstu na klucze do tłumaczeń pobierać nazwę i opis poprzez klucze
        foreach (Language::getLanguages() as $lang) {
            if ($lang['locale'] === "pl-PL") {
                $name_langs[$lang['id_lang']] =
                    $this->trans(
                        'Szybka płatność',
                        [],
                        'Modules.Bluepayment',
                        $lang['locale']
                    );
                $name_langs_group[$lang['id_lang']] =
                    $this->trans(
                        'Przelew internetowy',
                        [],
                        'Modules.Bluepayment',
                        $lang['locale']
                    );
            } else {
                $name_langs[$lang['id_lang']] =
                    $this->trans(
                        'Fast payment',
                        [],
                        'Modules.Bluepayment',
                        $lang['locale']
                    );
                $name_langs_group[$lang['id_lang']] =
                    $this->trans(
                        'Internet transfer',
                        [],
                        'Modules.Bluepayment',
                        $lang['locale']
                    );
            }
        }

        Configuration::updateValue($this->name_upper.'_PAYMENT_NAME', $name_langs);
        Configuration::updateValue($this->name_upper.'_PAYMENT_GROUP_NAME', $name_langs_group);

        return true;
    }
}

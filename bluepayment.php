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


use \Configuration as Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

require dirname(__FILE__).'/vendor/autoload.php';

class BluePayment extends PaymentModule
{
    public $name_upper;

    /**
     * Haki używane przez moduł
     */
    protected $hooks = [
            'header',
            'paymentOptions',
            'paymentReturn',
            'orderConfirmation',
            'displayBackOfficeHeader',
            'displayAdminAfterHeader',
            'adminOrder',
            'adminPayments',
            'displayBeforeBodyClosingTag',
            'displayProductPriceBlock',
            'displayBanner',
            'displayFooterBefore',
            'displayProductAdditionalInfo',
            'displayLeftColumn',
            'displayRightColumn',
            'displayShoppingCartFooter'
        ];
    public $id_order = null;
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
        $this->tracked_id = null;
        $this->name_upper = Tools::strtoupper($this->name);

        require_once dirname(__FILE__).'/config/config.inc.php';

        $this->tab = 'payments_gateways';
        $this->version = '2.7.7.1';
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
            $this->installSettings();

            $data = [
                'events' => [
                    "event_type" => "plugin installed",
                    "event_properties" => [
                        "plugin installed" => true,
                        "source" => 'Installation'
                    ],
                ],
            ];

            $amplitude = Amplitude::getInstance();
            $amplitude->sendEvent($data);

            return true;
        }

        return false;
    }




    public function installSettings()
    :bool {

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_SHOP, $this->context->shop->id);
            $shops = Shop::getContextListShopID();

            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop((int)$shop_id, true);

                Config::updateValue($this->name_upper.'_TEST_ENV', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_SHOW_PAYWAY', 1, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_GA_TYPE', 2, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_GA_TRACKER_ID', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_GA4_TRACKER_ID', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_GA4_SECRET', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_BLIK_REDIRECT', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_GPAY_REDIRECT', 0, false, $shop_group_id, $shop_id);

                Config::updateValue($this->name_upper.'_PROMO_PAY_LATER', 1, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_INSTALMENTS', 1, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_MATCHED_INSTALMENTS', 1, false, $shop_group_id, $shop_id);

                Config::updateValue($this->name_upper.'_PROMO_HEADER', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_FOOTER', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_LISTING', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_PRODUCT', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_CART', 0, false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PROMO_CHECKOUT', 1, false, $shop_group_id, $shop_id);

                Config::updateValue($this->name_upper.'_PAYMENT_NAME',
                    'Pay via Blue Media', false, $shop_group_id, $shop_id);
                Config::updateValue($this->name_upper.'_PAYMENT_GROUP_NAME',
                    'Przelew internetowy', false, $shop_group_id, $shop_id);
            }

            return true;

        } else {
            Config::updateValue($this->name_upper.'_TEST_ENV', 0, false);
            Config::updateValue($this->name_upper.'_SHOW_PAYWAY', 1, false);

            Config::updateValue($this->name_upper.'_GA_TYPE', 2, false);
            Config::updateValue($this->name_upper.'_GA_TRACKER_ID', 0, false);
            Config::updateValue($this->name_upper.'_GA4_TRACKER_ID', 0, false);
            Config::updateValue($this->name_upper.'_GA4_SECRET', 0, false);

            Config::updateValue($this->name_upper.'_BLIK_REDIRECT', 0, false);
            Config::updateValue($this->name_upper.'_GPAY_REDIRECT', 0, false);

            Config::updateValue($this->name_upper.'_PROMO_PAY_LATER', 1, false);
            Config::updateValue($this->name_upper.'_PROMO_INSTALMENTS', 1, false);
            Config::updateValue($this->name_upper.'_PROMO_MATCHED_INSTALMENTS', 1, false);

            Config::updateValue($this->name_upper.'_PROMO_HEADER', 0, false);
            Config::updateValue($this->name_upper.'_PROMO_FOOTER', 0, false);
            Config::updateValue($this->name_upper.'_PROMO_LISTING', 0, false);
            Config::updateValue($this->name_upper.'_PROMO_PRODUCT', 0, false);
            Config::updateValue($this->name_upper.'_PROMO_CART', 0, false);
            Config::updateValue($this->name_upper.'_PROMO_CHECKOUT', 1, false);

            Config::updateValue($this->name_upper.'_PAYMENT_NAME', 'Pay via Blue Media', false);
            Config::updateValue($this->name_upper.'_PAYMENT_GROUP_NAME', 'Przelew internetowy', false);

            return true;
        }

        PrestaShopLogger::addLog('Błąd wstępnej konfiguracji', 1);
        return false;
    }



    public function enable($force_all = false)
    {
        $data = [
            'events' => [
                "event_type" => "plugin activated",
                "user_properties" => [
                    "plugin activated" => true,
                ],
            ],
        ];
        $amplitude = Amplitude::getInstance();
        $amplitude->sendEvent($data);

        return parent::enable($force_all);
    }

    public function disable($force_all = false)
    {
        $data = [
            'events' => [
                "event_type" => "plugin deactivated",
                "user_properties" => [
                    "plugin activated" => false,
                ],
            ],
        ];

        $amplitude = Amplitude::getInstance();
        $amplitude->sendEvent($data);

        return parent::disable($force_all);
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

            $this->context->smarty->assign(['version' => $ver]);

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
    :bool {
        try {
            CustomStatus::addOrderStates($this->context->language->id, $this->name_upper);
            return true;
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Add statuses - error', 4);
            return false;
        }
    }

    /**
     * Remove module
     */
    public function uninstall()
    :bool
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
                Config::deleteByName($configField);
            }

            Config::deleteByName($this->name_upper.'_SHARED_KEY');
            Config::deleteByName($this->name_upper.'_SERVICE_PARTNER_ID');

            $data = [
                'events' => [
                    "event_type" => "plugin uninstalled",
                    "event_properties" => [
                        "plugin installed" => false,
                        "source" => 'Installation'
                    ],
                ],
            ];

            $amplitude = Amplitude::getInstance();
            $amplitude->sendEvent($data);
            return true;
        }

        return false;
    }

    /**
     * Install tab controller AdminBluepaymentController
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
     */
    public function uninstallTab()
    :bool
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
     */
    public function addTabInPayments()
    :bool
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
     */
    public function removeTabInPayments()
    :bool
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

    public function getPathUrl()
    {
        return $this->_path;
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
        $id_shop = $this->context->shop->id;

        $query = new DbQuery();
        $query->select('gc.*, gcs.id_shop');
        $query->from('blue_gateway_channels', 'gc');
        $query->leftJoin('blue_gateway_channels_shop', 'gcs',
            'gc.id_blue_gateway_channels = gcs.id_blue_gateway_channels');

        $query->where('gc.gateway_currency = "'.pSql($currency).'"');

        if (Shop::isFeatureActive()) {
            $query->where('gcs.id_shop = ' . (int)$id_shop);
        }

        $query->orderBy('gc.position ASC');
        $query->groupBy('gc.id_blue_gateway_channels');

        return Db::getInstance()->ExecuteS($query);
    }

    public function getListAllPayments($currency = 'PLN', $type = null)
    {

        $id_shop = $this->context->shop->id;

        $q = '';
        if ($type === 'wallet') {
            $q = 'IN ("Apple Pay","Google Pay")';
        } elseif ($type === 'transfer') {
            $q = 'NOT IN ("BLIK","Apple Pay","Google Pay","PBC płatność testowa","Płatność kartą","Kup teraz, zapłać później","Alior Raty")';
        }

        $query = new DbQuery();

        $query->select('gt.*');
        $query->from('blue_gateway_transfers', 'gt');

        $query->leftJoin('blue_gateway_transfers_shop', 'gcs', 'gcs.id = gt.id');

        $query->where('gt.gateway_name ' .$q);
        $query->where('gt.gateway_currency = "'.pSql($currency).'"');

        if (Shop::isFeatureActive()) {
            $query->where('gcs.id_shop = ' . (int)$id_shop);
        }

        $query->orderBy('gt.position ASC');
        $query->groupBy('gt.id');

        return Db::getInstance()->ExecuteS($query);
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

        $position_helper = $this->display(__FILE__,
            'views/templates/admin/_configure/helpers/form/notification-info.tpl');

        $this->context->smarty->assign(
            [
                'list' => $list,
                'transfer_payments' => $transfer_payments,
                'wallets' => $wallets,
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/_configure/helpers/container_list.tpl');
    }

    /**
     * Sortowanie walut po id
     *
     * @return array
     */

    public function getSortCurrencies(){
        $sortCurrencies = Currency::getCurrenciesByIdShop($this->context->shop->id);

        usort($sortCurrencies, function ($a, $b) {
            if ($a['id_currency'] == $b['id_currency']) {
                return 0;
            }
            return $a['id_currency'] > $b['id_currency'] ? 1 : -1;
        });
        return (array)$sortCurrencies;
    }


    public function displayGatewayLogo($gatewayLogo, $object)
    {

        $currency = $object['gateway_currency'];

        if($gatewayLogo === '/modules/bluepayment/views/img/payments.png') {
            $result = '<div class="bm-slideshow-wrapper">';
            $result .= '<div class="bm-transfers'.$currency.'-slideshow bm-slideshow" data-slideshow="transfers'.$currency.'">';
            foreach($this->getImgPayments('transfers') as $row) {
                $result .= '<div class="slide">';
                $result .= '<img src="'.$row['gateway_logo_url'].'" alt="'.$row['gateway_name'].'">';
                $result .= '</div>';
            }
            $result .= '</div>';
            $result .= '</div>';
        } else if($gatewayLogo === '/modules/bluepayment/views/img/cards.png') {
            $result = '<div class="bm-slideshow-wrapper">';
            $result .= '<div class="bm-wallet'.$currency.'-slideshow bm-slideshow" data-slideshow="wallet'.$currency.'">';
            foreach($this->getImgPayments('wallet') as $row) {
                $result .= '<div class="slide">';
                $result .= '<img src="'.$row['gateway_logo_url'].'" alt="'.$row['gateway_name'].'">';
                $result .= '</div>';
            }
            $result .= '</div>';
            $result .= '</div>';
        } else {
            $result = '<img width="65" class="img-fluid" src="'.$gatewayLogo.'" />';
        }

        return $result;
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
            $data[$configField] = Tools::getValue($configField, Config::get($configField));
        }

        foreach (Language::getLanguages(true) as $lang) {
            if(Config::get($this->name_upper.'_PAYMENT_NAME', $lang['id_lang'])) {
                $data[$this->name_upper.'_PAYMENT_NAME'][$lang['id_lang']] =
                Config::get($this->name_upper.'_PAYMENT_NAME', $lang['id_lang']);
            }

            if(Config::get($this->name_upper.'_PAYMENT_GROUP_NAME', $lang['id_lang'])) {
                $data[$this->name_upper.'_PAYMENT_GROUP_NAME'][$lang['id_lang']] =
                Config::get($this->name_upper.'_PAYMENT_GROUP_NAME', $lang['id_lang']);
            }
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
        $data = Tools::unSerialize(Config::get($key));
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
            $this->name_upper.'_TEST_ENV',

            $this->name_upper.'_GA_TYPE',
            $this->name_upper.'_GA_TRACKER_ID',
            $this->name_upper.'_GA4_TRACKER_ID',
            $this->name_upper.'_GA4_SECRET',

            $this->name_upper.'_BLIK_REDIRECT',
            $this->name_upper.'_GPAY_REDIRECT',
            $this->name_upper.'_PROMO_PAY_LATER',
            $this->name_upper.'_PROMO_INSTALMENTS',
            $this->name_upper.'_PROMO_MATCHED_INSTALMENTS',
            $this->name_upper.'_PROMO_HEADER',
            $this->name_upper.'_PROMO_FOOTER',
            $this->name_upper.'_PROMO_LISTING',
            $this->name_upper.'_PROMO_PRODUCT',
            $this->name_upper.'_PROMO_CART',
            $this->name_upper.'_PROMO_CHECKOUT',
        ];
    }

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
                    $history->changeIdOrderState(Config::get('PS_OS_REFUND'), (int)$order->id);
                    $history->addWithemail(true, []);
                    $refund_success[] = $this->l('Successful refund');
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



    private function getWalletsList() {
        $wallets_array = [
            GATEWAY_ID_GOOGLE_PAY,
            GATEWAY_ID_APPLE_PAY
        ];

        return implode(',', $wallets_array);
    }


    private function getImgPayments($type) {

        $currency = $this->context->currency;
        $id_shop = $this->context->shop->id;
        $query = null;

        if($type === 'transfers') {
            $query = new DbQuery();
            $query->from('blue_gateway_transfers', 'gt');
            $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
            $query->where('gt.gateway_id != ' . GATEWAY_ID_BLIK);
            $query->where('gt.gateway_id != ' . GATEWAY_ID_ALIOR);
            $query->where('gt.gateway_id != ' . GATEWAY_ID_CARD);
            $query->where('gt.gateway_id != ' . GATEWAY_ID_GOOGLE_PAY);
            $query->where('gt.gateway_id != ' . GATEWAY_ID_APPLE_PAY);
            $query->where('gt.gateway_id != ' . GATEWAY_ID_SMARTNEY);
            $query->where('gt.gateway_status = 1');
            $query->where('gt.gateway_currency = "'.pSql($currency->iso_code).'"');

            if (Shop::isFeatureActive()) {
                $query->where('gts.id_shop = ' . (int)$id_shop);
            }
            $query->select('gateway_logo_url, gateway_name');

        } elseif ($type === 'wallet') {
            $query = new DbQuery();
            $query->from('blue_gateway_transfers', 'gt');
            $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
            $query->where('gt.gateway_id IN (' . $this->getWalletsList() . ')');
            $query->where('gt.gateway_status = 1');
            $query->where('gt.gateway_currency = "'.pSql($currency->iso_code).'"');

            if (Shop::isFeatureActive()) {
                $query->where('gts.id_shop = ' . (int)$id_shop);
            }

            $query->select('gateway_logo_url, gateway_name');
        }

        return Db::getInstance()->executeS($query);


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
        $id_shop = $this->context->shop->id;

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
        $alior = false;
        $cardGateway = false;

        require_once dirname(__FILE__).'/sdk/index.php';

        /// Get all transfers
        $gateway_transfers = new DbQuery();
        $gateway_transfers->from('blue_gateway_transfers', 'gt');
        $gateway_transfers->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_BLIK);
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_ALIOR);
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_CARD);
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_GOOGLE_PAY);
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_APPLE_PAY);
        $gateway_transfers->where('gt.gateway_id != ' . GATEWAY_ID_SMARTNEY);
        $gateway_transfers->where('gt.gateway_status = 1');
        $gateway_transfers->where('gt.gateway_currency = "'.pSql($currency->iso_code).'"');

        if (Shop::isFeatureActive()) {
            $gateway_transfers->where('gts.id_shop = ' . (int)$id_shop);
        }

        $gateway_transfers->select('*');
        $gateway_transfers = Db::getInstance()->executeS($gateway_transfers);

        /// Get all wallets
        $gateway_wallets = new DbQuery();
        $gateway_wallets->from('blue_gateway_transfers', 'gt');
        $gateway_wallets->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $gateway_wallets->where('gt.gateway_id IN (' . $this->getWalletsList() . ')');
        $gateway_wallets->where('gt.gateway_status = 1');
        $gateway_wallets->where('gt.gateway_currency = "'.pSql($currency->iso_code).'"');

        if (Shop::isFeatureActive()) {
            $gateway_wallets->where('gts.id_shop = ' . (int)$id_shop);
        }

        $gateway_wallets->select('*');
        $gateway_wallets1 = Db::getInstance()->executeS($gateway_wallets);

        $cart_id_time = $this->context->cart->id.'-'.time();


        $promo_checkout = Config::get($this->name_upper . '_PROMO_CHECKOUT');

        $this->smarty->assign([
            'module_link' => $moduleLink,
            'ps_version' => _PS_VERSION_,
            'module_dir' => $this->getPathUrl(),
            'payment_name' => Config::get($this->name_upper.'_PAYMENT_NAME', $this->context->language->id),
            'payment_group_name' =>
                Config::get($this->name_upper.'_PAYMENT_GROUP_NAME', $this->context->language->id),
            'selectPayWay' => Config::get($this->name_upper.'_SHOW_PAYWAY'),
            'gateway_transfers' => $gateway_transfers,
            'gateway_wallets' => $gateway_wallets1,
            'img_wallets' => $this->getImgPayments('wallet'),
            'img_transfers' => $this->getImgPayments('transfers'),
            'regulations_get' => $this->context->link->getModuleLink('bluepayment', 'regulationsGet', [], true),
            'changePayment' => $this->l('change'),
            'bm_promo_checkout' => $promo_checkout,
            'gpayRedirect' => Config::get($this->name_upper.'_GPAY_REDIRECT'),

            'start_payment_translation' => $this->l('Start payment'),
            'start_payment_intro' => $this->l('Internet transfer, BLIK, payment card, Google Pay, Apple Pay'),
            'order_subject_to_payment_obligation_translation' => $this->l('Order with the obligation to pay'),
        ]);

        $newOptions = [];

        if (Config::get($this->name_upper.'_SHOW_PAYWAY')) {

            /**
             * Tworzenie grupy płatności
             */
            $blik = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_BLIK, $currency->iso_code);
            $cardGateway = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_CARD, $currency->iso_code);
            $gpay = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_GOOGLE_PAY, $currency->iso_code);
            $smartney = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_SMARTNEY, $currency->iso_code);
            $applePay = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_APPLE_PAY, $currency->iso_code);
            $alior = BlueGatewayTransfers::gatewayIsActive(GATEWAY_ID_ALIOR, $currency->iso_code);

            /**
             * Pobieranie grup płatności
             */
            $payment_group = new DbQuery();
            $payment_group->select('*');
            $payment_group->from('blue_gateway_channels', 'gt');
            $payment_group->leftJoin('blue_gateway_channels_shop', 'gts', 'gts.id_blue_gateway_channels = gt.id_blue_gateway_channels');
            $payment_group->where('gt.gateway_status = 1');
            $payment_group->where('gt.gateway_currency = "'.pSql($currency->iso_code).'"');

            if (Shop::isFeatureActive()) {
                $payment_group->where('gts.id_shop = ' . (int)$id_shop);
            }

            $payment_group->orderBy('gt.position');
            $payment_group = Db::getInstance()->executeS($payment_group);

            if (!empty($payment_group)) {
                foreach ($payment_group as $p_group) {

                    if ($p_group['gateway_name'] === 'Przelew internetowy') {
                        $paymentName = Config::get(
                            $this->name_upper.'_PAYMENT_GROUP_NAME',
                            $this->context->language->id
                        );

                        if (!empty($gateway_transfers)) {
                            $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                            $newOption->setCallToActionText($paymentName)
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

                    if ($p_group['gateway_name'] === 'PBC płatność testowa' ||
                        $p_group['gateway_name'] === 'Płatność kartą') {
                        if ($cardGateway) {
                            $card = new BlueGatewayTransfers($cardGateway);
                            $cardOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                            $cardOption->setAdditionalInformation(
                                    $this->fetch('module:bluepayment/views/templates/hook/paymentRedirectCard.tpl')
                                );
                            $cardOption->setCallToActionText($card->gateway_name)
                                ->setAction($moduleLink)
                                ->setInputs([
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway',
                                        'value' => GATEWAY_ID_CARD,
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway_id',
                                        'value' => GATEWAY_ID_CARD,
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

                    if ($p_group['gateway_name'] === 'BLIK') {
                        if ($blik) {
                            $blikGateway = new BlueGatewayTransfers($blik);
                            if (Config::get($this->name_upper.'_BLIK_REDIRECT')) {
                                $blikOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                                $blikOption->setCallToActionText($blikGateway->gateway_name)->setAction($moduleLink)
                                    ->setInputs([
                                        [
                                            'type' => 'hidden',
                                            'name' => 'bluepayment_gateway',
                                            'value' => GATEWAY_ID_BLIK,
                                        ],
                                        [
                                            'type' => 'hidden',
                                            'name' => 'bluepayment_gateway_id',
                                            'value' => GATEWAY_ID_BLIK,
                                        ]
                                    ])
                                    ->setLogo($blikGateway->gateway_logo_url)
                                    ->setAdditionalInformation(
                                        $this->fetch('module:bluepayment/views/templates/hook/paymentRedirectBlik.tpl')
                                    );
                                $newOptions[] = $blikOption;
                            } else {
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

                                $blikOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                                $blikOption->setCallToActionText($blikGateway->gateway_name)
                                    ->setAction($blikModuleLink)
                                    ->setBinary(true)
                                    ->setLogo($blikGateway->gateway_logo_url)
                                    ->setAdditionalInformation(
                                        $this->fetch('module:bluepayment/views/templates/hook/paymentBlik.tpl')
                                    );
                                $newOptions[] = $blikOption;
                            }
                        }
                    }

                    if ($p_group['gateway_name'] === 'Wirtualny portfel') {
                        /**
                         * G-pay button will show only in secure enviroments, it mean:
                         * 127.0.0.1, localhost, secure SSL host
                         */

//                        if (!empty($gateway_wallets1) ) {

                        if (!empty($gateway_wallets1) && ($gpay || $applePay)) {

                            $walletMerchantInfo = $this->context->link->getModuleLink(
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

                            $gpayRedirect = false;
                            if (Config::get($this->name_upper.'_GPAY_REDIRECT')) {
                                $gpayRedirect = true;
                            }

                            $this->smarty->assign([
                                'wallet_merchantInfo' => $walletMerchantInfo,
                                'gpay_redirect' => $gpayRedirect,
                                'gpay_moduleLinkCharge' => $gpay_moduleLinkCharge,
                                'googlePay' => $gpay,
                                'applePay' => $applePay,
                            ]);

                            $walletOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                            $walletOption->setCallToActionText($this->l('Virtual wallet'))
                                ->setAction($moduleLink)
                                ->setLogo($this->context->shop->getBaseURL(true).'modules/bluepayment/views/img/blue-media.svg')
                                ->setAdditionalInformation(
                                    $this->fetch('module:bluepayment/views/templates/hook/wallet.tpl')
                                );

                            $walletOption->setInputs([
                                [
                                    'type' => 'hidden',
                                    'name' => 'bluepayment_gateway',
                                    'value' => 0,
                                ],
                                [
                                    'type'  => 'hidden',
                                    'name'  => 'gpay_get_merchant_info',
                                    'value' => $walletMerchantInfo,
                                ]
                            ]);

                            $newOptions[] = $walletOption;
                        }
                    }

                    if ($p_group['gateway_name'] === 'Kup teraz, zapłać później') {
                        if ($smartney
                            && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                            >= (float)SMARTNEY_MIN_AMOUNT
                            && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                            <= (float)SMARTNEY_MAX_AMOUNT
                        ) {
                            $smartneyGateway = new BlueGatewayTransfers($smartney);
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
                            $smartneyOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                            $smartneyOption->setAdditionalInformation(
                                $this->fetch('module:bluepayment/views/templates/hook/paymentRedirectSmartney.tpl')
                            );
                            $smartneyOption->setCallToActionText($smartneyGateway->gateway_name)
                                ->setAction($moduleLink)
                                ->setLogo($smartneyGateway->gateway_logo_url)
                                ->setInputs([
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway',
                                        'value' => GATEWAY_ID_SMARTNEY,
                                    ],
                                    [
                                        'type' => 'hidden',
                                        'name' => 'bluepayment_gateway_id',
                                        'value' => GATEWAY_ID_SMARTNEY,
                                    ],
                                ]);
                            $newOptions[] = $smartneyOption;
                        }
                    }

                    if ($alior
                        && $p_group['gateway_name'] === 'Alior Raty'
                        && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                        >= (float)ALIOR_MIN_AMOUNT
                        && (float)$this->context->cart->getOrderTotal(true, Cart::BOTH)
                        <= (float)ALIOR_MAX_AMOUNT
                    ) {
                        $aliorGateway = new BlueGatewayTransfers($alior);
                        $aliorOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                        $aliorOption->setAdditionalInformation(
                            $this->fetch('module:bluepayment/views/templates/hook/paymentRedirectAliorbank.tpl')
                        );
                        $aliorOption->setCallToActionText($aliorGateway->gateway_name)
                            ->setAction($moduleLink)
                            ->setInputs([
                                [
                                    'type' => 'hidden',
                                    'name' => 'bluepayment_gateway',
                                    'value' => GATEWAY_ID_ALIOR,
                                ],
                                [
                                    'type' => 'hidden',
                                    'name' => 'bluepayment_gateway_id',
                                    'value' => GATEWAY_ID_ALIOR,
                                ],
                            ])
                            ->setLogo($aliorGateway->gateway_logo_url);
                        $newOptions[] = $aliorOption;
                    }
                }
            }

        } else {
            /**
             * Tworzenie przekierowania dla wszystkich płatności
             */
            $paymentName = Config::get($this->name_upper.'_PAYMENT_NAME', $this->context->language->id);

            $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
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
        $id_default_lang = (int)Config::get('PS_LANG_DEFAULT');
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
        Media::addJsDef([
            'bluepayment_env' => (int)Config::get($this->name_upper.'_TEST_ENV') === 1 ? 'TEST' : 'PRODUCTION',
            'asset_path' => $this->getPathUrl() . 'views/',
            'change_payment' => $this->l('change'),
            'read_more' => $this->l('read more'),
            'get_regulations_url' => $this->context->link->getModuleLink('bluepayment', 'regulationsGet', [], true),
        ]);

        $this->context->controller->addCSS($this->_path.'views/css/front.css');
        $this->context->controller->addJS($this->_path.'views/js/front.min.js');
        $this->context->controller->addJS($this->_path.'views/js/blik_v3.js');
        $this->context->controller->addJS($this->_path.'views/js/gpay.js');
    }

    /**
     * Gtag data
     */

    public function hookdisplayBeforeBodyClosingTag()
    {
        $controller = Tools::getValue('controller');

        $tracking_id = false;
        $secret_key = false;

        if(Config::get('BLUEPAYMENT_GA_TRACKER_ID')) {
            $tracking_id = Config::get('BLUEPAYMENT_GA_TRACKER_ID');
        } else if (Config::get('BLUEPAYMENT_GA4_TRACKER_ID') && Config::get('BLUEPAYMENT_GA4_SECRET')) {
            $tracking_id = Config::get('BLUEPAYMENT_GA4_TRACKER_ID');
            $secret_key = Config::get('BLUEPAYMENT_GA4_SECRET');
        }

        $this->context->smarty->assign([
            'tracking_id' => $tracking_id,
            'tracking_secret_key' => $secret_key,
            'controller' => $controller,
            'bm_ajax_controller' => $this->context->link->getModuleLink(
                $this->name, 'ajax',
                array('ajax' => 1
                )
            )
        ]);

        if($controller == 'cart') {
            $this->context->smarty->assign([
                'products' => $this->context->cart->getProducts(false, false, null, false),
            ]);
        } elseif ($controller == 'order') {
            $coupons_array = [];
            $coupons_list = '';

            if($this->context->cart->getCartRules()){
                foreach($this->context->cart->getCartRules() as $coupon){
                    $coupons_array[] = $coupon['name'];
                }
                $coupons_list = implode(", ", $coupons_array);
            }

            $this->context->smarty->assign([
                'products' => $this->context->cart->getProducts(true),
                'coupons' => $coupons_list,
            ]);
        }

        return $this->display($this->local_path, 'views/templates/hook/gtag.tpl');
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

    public function debug( $texto ) {
        $logfilename = dirname( __FILE__ ) . '/log.log';
        file_put_contents( $logfilename, print_r( $texto, true ) );
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
        $stateOrderTab = [Config::get('PS_OS_CANCELED')];

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
        $status_accept_pay_id = Config::get($this->name_upper.'_STATUS_ACCEPT_PAY_ID');
        $status_waiting_pay_id = Config::get($this->name_upper.'_STATUS_WAIT_PAY_ID');
        $status_error_pay_id = Config::get($this->name_upper.'_STATUS_ERROR_PAY_ID');

        // Status płatności
        $payment_status = pSql((string)$transaction->paymentStatus);

        // Id transakcji nadany przez bramkę
        $remote_id = pSql((string)$transaction->remoteID);

        // Id zamówienia
        $realOrderId = pSql((string)$transaction->orderID);
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





                    /// Send GA event
                    if(Config::get('BLUEPAYMENT_GA_TRACKER_ID') ||
                        (Config::get('BLUEPAYMENT_GA4_TRACKER_ID') && Config::get('BLUEPAYMENT_GA4_SECRET'))
                    ){

                        /// Get ga user session
                        $query = new DbQuery();
                        $query->from('blue_transactions')
                            ->where('order_id like "'.pSQL($order_id).'-%"')
                            ->where('gtag_state IS NULL')
                            ->select('gtag_uid');
                        $ga_cid = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query, false);

                        if($ga_cid) {

                            $args = [];
                            $order_ga = new OrderCore($order_id);


                            if ($order_ga->getProducts()) {
                                $p_key = 0;
                                foreach ($order_ga->getProducts() as $key => $p) {

                                    $brand = null;
                                    $category_name = null;

                                    if ($p['id_manufacturer']) {
                                        $brand = Manufacturer::getNameById($p['id_manufacturer']);
                                    }

                                    $cat = new Category($p['id_category_default'], $this->context->language->id);
                                    if ($cat) {
                                        $category_name = $cat->name;
                                    }

                                    $p_key++;

                                    if (Config::get('BLUEPAYMENT_GA_TYPE') === '1') {

                                        $args['pr'.$p_key.'id'] = $p['product_id'];
                                        $args['pr'.$p_key.'nm'] = Product::getProductName($p['product_id']);
                                        $args['pr'.$p_key.'br'] = $brand;
                                        $args['pr'.$p_key.'ca'] = $category_name;
                                        //                                $args['pr'.$p_key.'va'] = $attr;
                                        $args['pr'.$p_key.'pr'] = $p['total_price_tax_incl'];
                                        $args['pr'.$p_key.'qt'] = $p['product_quantity'];

                                    } else if (Config::get('BLUEPAYMENT_GA_TYPE') === '2') {

                                        $items = [
                                                [
                                                'item_id' => $p['product_id'],
                                                'item_name' => Product::getProductName($p['product_id']),
                                                'value' => $brand,
                                                'item_brand' => $category_name,
                                                'item_category' => $category_name,
                                                'price' => $p['total_price_tax_incl'],
                                                'quantity' => $p['product_quantity'],
                                                ]
                                        ];
                                    }
                                }
                            }

                                if (Config::get('BLUEPAYMENT_GA_TYPE') === '1') {
                                    /// GA Universal
                                    $analitics = new AnalyticsTracking(Config::get('BLUEPAYMENT_GA_TRACKER_ID'), $ga_cid);

                                    $args['cu'] = $this->context->currency->iso_code;
                                    $args['ti'] = $order_ga->id_cart.'-'.time();
                                    $args['tr'] = $order_ga->total_paid_tax_incl;
                                    $args['tt'] = $order_ga->total_paid - $order_ga->total_paid_tax_excl;
                                    $args['ts'] = $order_ga->total_shipping_tax_incl;
                                    $args['pa'] = 'purchase';
                                    $analitics->gaSendEvent('ecommerce', 'purchase', 'accepted', $args);
                                } else if (Config::get('BLUEPAYMENT_GA_TYPE') === '2') {
                                    /// GA4
                                    $analitics = new AnalyticsTracking(
                                        Config::get('BLUEPAYMENT_GA4_TRACKER_ID'),
                                        $ga_cid,
                                        Config::get('BLUEPAYMENT_GA4_SECRET')
                                    );

                                    $args['events'][] = [
                                        'name' => 'purchase',
                                        'params' => [
                                            'items' => $items,
                                            'currency' => $this->context->currency->iso_code,
                                            'transaction_id' => $order_ga->id_cart.'-'.time(),
                                            'value' => $order_ga->total_paid_tax_incl,
                                            'tax' => $order_ga->total_paid - $order_ga->total_paid_tax_excl,
                                            'shipping' => $order_ga->total_shipping_tax_incl,
                                        ],
                                    ];
                                    $args['user_id'] = $order_ga->id_customer;

                                    $analitics->ga4SendEvent($args);
                                }



                            /// Reset state
                            $transactionData = [
                                'gtag_state' => 1,
                            ];

                            Db::getInstance()->update('blue_transactions', $transactionData, 'order_id like "'.pSQL($order_id).'-%"');

                        }
                    }


                    if ($order->current_state == $status_waiting_pay_id ||
                        $order->current_state == $status_error_pay_id
                    ) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->changeIdOrderState($status_accept_pay_id, $order_id);
                        $new_history->addWithemail(true);
                        if ((string)$transaction->gatewayID == (string)GATEWAY_ID_BLIK) {
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

        Config::updateValue($this->name_upper.'_PAYMENT_NAME', $name_langs);
        Config::updateValue($this->name_upper.'_PAYMENT_GROUP_NAME', $name_langs_group);

        return true;
    }

    /**
     * Add analytics Gtag
     *
     * @param $params
     * @return void
     */

    public function hookDisplayProductPriceBlock($params) {
        if($params['type'] === 'before_price') {

            $product = $params['product'];
            $brand = '';

            if(isset($product['id_manufacturer'])) {
                $brand = Manufacturer::getNameById($product['id_manufacturer']);
            }

            $this->context->smarty->assign([
                'ga_product_id' => $product['id'],
                'ga_product_name' => $product['name'],
                'ga_product_brand' => $brand,
                'ga_product_cat' => $product['category_name'],
                'ga_product_price' => $product['price'],
            ]);

            return $this->fetch('module:bluepayment/views/templates/hook/ga_listing.tpl');

        }
    }


    /**
     * Adds promoted payments to the top of the page
     */
    public function hookDisplayBanner($params) {
        if(Configuration::get($this->name_upper . '_PROMO_HEADER')) {
            $this->getSmartyAssets('main');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/header.tpl');
        }
        return false;
    }

    /**
     * Adds promoted payments above the footer
     */
    public function hookDisplayFooterBefore($params) {
        if(Config::get($this->name_upper . '_PROMO_FOOTER')) {
            $this->getSmartyAssets('main');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/footer.tpl');
        }
        return false;
    }

    /**
     * Adds promoted payments under the buttons in the product page
     */
    public function hookDisplayProductAdditionalInfo($params) {
        if(Config::get($this->name_upper . '_PROMO_PRODUCT')) {
            $this->getSmartyAssets('product');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/product.tpl');
        }
        return false;
    }


    /**
     * Adds promoted payments in the left column on the category subpage
     */
    public function hookDisplayLeftColumn($params) {
        if(Config::get($this->name_upper . '_PROMO_LISTING')) {
            $this->getSmartyAssets('sidebar');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/labels.tpl');
        }
        return false;
    }

    /**
     * Adds promoted payments in the right column on the category subpage
     */
    public function hookDisplayRightColumn($params) {
        if(Config::get($this->name_upper . '_PROMO_LISTING')) {
            $this->getSmartyAssets('sidebar');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/labels.tpl');
        }
        return false;
    }

    /**
     * Adds promoted payments in the shopping cart under products
     */
    public function hookDisplayShoppingCartFooter($params) {
        if(Config::get($this->name_upper . '_PROMO_CART')) {
            $this->getSmartyAssets('cart');
            return $this->fetch('module:bluepayment/views/templates/hook/labels/labels.tpl');
        }
        return false;
    }

    private function getSmartyAssets($type = 'main') {
        $pay_later = Config::get($this->name_upper . '_PROMO_PAY_LATER');
        $instalment = Config::get($this->name_upper . '_PROMO_INSTALMENTS');
        $matched_instalments = Config::get($this->name_upper . '_PROMO_MATCHED_INSTALMENTS');
        $promo_checkout = Config::get($this->name_upper . '_PROMO_CHECKOUT');

        return $this->context->smarty->assign([
            'bm_assets_images' => $this->images_dir,
            'bm_instalment' => $instalment,
            'bm_pay_later' => $pay_later,
            'bm_matched_instalments' => $matched_instalments,
            'bm_promo_checkout' => $promo_checkout,
            'bm_promo_type' => $type
        ]);
    }

}

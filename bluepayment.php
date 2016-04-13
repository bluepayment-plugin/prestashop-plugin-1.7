<?php

/**
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2016
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('_PS_VERSION_'))
    exit;

class BluePayment extends PaymentModule {

    private $html = '';

    /**
     * Haki używane przez moduł
     *
     * @var array
     */
    protected $hooks = array(
        'header',
        'payment',
        'paymentReturn',
    );
    private $_checkHashArray = [];

    /**
     * Stałe statusów płatności
     */
    const PAYMENT_STATUS_PENDING = 'PENDING';
    const PAYMENT_STATUS_SUCCESS = 'SUCCESS';
    const PAYMENT_STATUS_FAILURE = 'FAILURE';

    /**
     * Stałe potwierdzenia autentyczności transakcji
     */
    const TRANSACTION_CONFIRMED = "CONFIRMED";
    const TRANSACTION_NOTCONFIRMED = "NOTCONFIRMED";

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->name = 'bluepayment';
        $this->name_upper = strtoupper($this->name);
        // Kompatybilność wstecz dla wersji 1.4
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
            require_once(_PS_MODULE_DIR_ . $this->name . '/config/config.inc.php');
        } else {
            require_once(dirname(__FILE__) . '/config/config.inc.php');
        }
        $this->tab = 'payments_gateways';
        $this->version = BP_VERSION;
        $this->author = 'Blue Media';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4.5', 'max' => '1.6');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Online payment BM');
        $this->description = $this->l('Plugin supports online payments implemented by payment gateway Blue Media company.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Instalacja dodatku
     *
     * @return bool
     */
    public function install() {
        if (parent::install()) {
            foreach ($this->hooks as $hook) {
                if (!$this->registerHook($hook))
                    return false;
            }
            // Domyślne ustawienie aktywnego trybu testowego
            Configuration::updateValue($this->name_upper . '_TEST_MODE', 1);

            return true;
        }
        return false;
    }

    /**
     * Usunięcie dodatku
     *
     * @return bool
     */
    public function uninstall() {
        if (parent::uninstall()) {
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook))
                    return false;
            }
            // Usunięcie aktualnych wartości konfiguracyjnych
            Configuration::deleteByName($this->name_upper . '_TEST_MODE');
            Configuration::deleteByName($this->name_upper . '_SERVICE_PARTNER_ID');
            Configuration::deleteByName($this->name_upper . '_SHARED_KEY');
            Configuration::deleteByName($this->name_upper . '_STATUS_WAIT_PAY_ID');
            Configuration::deleteByName($this->name_upper . '_STATUS_ACCEPT_PAY_ID');
            Configuration::deleteByName($this->name_upper . '_STATUS_ERROR_PAY_ID');

            return true;
        }
        return false;
    }

    /**
     * Zwraca zawartość strony konfiguracyjnej
     *
     * @return string
     */
    public function getContent() {
        $output = null;

        // Kompatybilność wstecz dla wersji 1.4
        if (_PS_VERSION_ < '1.5') {
            $this->postProcess();
            return $this->displayForm();
        }

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue($this->name_upper . '_TEST_MODE', (int) Tools::getValue($this->name_upper . '_TEST_MODE'));
            Configuration::updateValue($this->name_upper . '_SERVICE_PARTNER_ID', Tools::getValue($this->name_upper . '_SERVICE_PARTNER_ID'));
            Configuration::updateValue($this->name_upper . '_SHARED_KEY', Tools::getValue($this->name_upper . '_SHARED_KEY'));
            Configuration::updateValue($this->name_upper . '_STATUS_WAIT_PAY_ID', Tools::getValue($this->name_upper . '_STATUS_WAIT_PAY_ID'));
            Configuration::updateValue($this->name_upper . '_STATUS_ACCEPT_PAY_ID', Tools::getValue($this->name_upper . '_STATUS_ACCEPT_PAY_ID'));
            Configuration::updateValue($this->name_upper . '_STATUS_ERROR_PAY_ID', Tools::getValue($this->name_upper . '_STATUS_ERROR_PAY_ID'));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output . $this->renderForm();
    }

    /**
     * Zwraca formularz
     *
     * @return mixed
     */
    public function renderForm() {
        // Domyślny język
        $id_default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Dostępne statusy
        $statuses = OrderState::getOrderStates($id_default_lang);

        // Pola do konfiguracji
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Test mode'),
                    'required' => true,
                    'name' => $this->name_upper . '_TEST_MODE',
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Service partner ID'),
                    'name' => $this->name_upper . '_SERVICE_PARTNER_ID',
                    'size' => 40,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Shared key'),
                    'name' => $this->name_upper . '_SHARED_KEY',
                    'size' => 40,
                    'required' => true
                ),
                array(
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_WAIT_PAY_ID',
                    'label' => $this->l('Status waiting payment'),
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_ACCEPT_PAY_ID',
                    'label' => $this->l('Status accept payment'),
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'select',
                    'name' => $this->name_upper . '_STATUS_ERROR_PAY_ID',
                    'label' => $this->l('Status error payment'),
                    'options' => array(
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name'
                    ),
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

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
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' =>
            array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($fields_form);
    }

    /**
     * Zwraca tablicę pól konfiguracyjnych
     *
     * @return array
     */
    public function getConfigFieldsValues() {
        return array(
            $this->name_upper . '_TEST_MODE' => Tools::getValue($this->name_upper .
                    '_TEST_MODE', Configuration::get($this->name_upper . '_TEST_MODE')),
            $this->name_upper . '_SERVICE_PARTNER_ID' => Tools::getValue($this->name_upper .
                    '_SERVICE_PARTNER_ID', Configuration::get($this->name_upper . '_SERVICE_PARTNER_ID')),
            $this->name_upper . '_SHARED_KEY' => Tools::getValue($this->name_upper .
                    '_SHARED_KEY', Configuration::get($this->name_upper . '_SHARED_KEY')),
            $this->name_upper . '_STATUS_WAIT_PAY_ID' => Tools::getValue($this->name_upper .
                    '_STATUS_WAIT_PAY_ID', Configuration::get($this->name_upper . '_STATUS_WAIT_PAY_ID')),
            $this->name_upper . '_STATUS_ACCEPT_PAY_ID' => Tools::getValue($this->name_upper .
                    '_STATUS_ACCEPT_PAY_ID', Configuration::get($this->name_upper . '_STATUS_ACCEPT_PAY_ID')),
            $this->name_upper . '_STATUS_ERROR_PAY_ID' => Tools::getValue($this->name_upper .
                    '_STATUS_ERROR_PAY_ID', Configuration::get($this->name_upper . '_STATUS_ERROR_PAY_ID'))
        );
    }

    /**
     * Hak do kroku wyboru płatności
     */
    public function hookPayment() {
        if (!$this->active)
            return;

        // Kompatybilność wstecz dla wersji 1.4
        if (_PS_VERSION_ < '1.5') {
            global $smarty;
            $this->smarty = $smarty;
        }

        if (method_exists('Link', 'getModuleLink')) {
            $moduleLink = $this->context->link->getModuleLink('bluepayment', 'payment', array(), true);
            $tpl = 'payment.tpl';
        } else {
            $tpl = '/views/templates/hook/payment.tpl';
            $moduleLink = __PS_BASE_URI__ . 'modules/' . $this->name . '/payment.php';
        }

        $this->smarty->assign(array(
            'module_link' => $moduleLink,
            'ps_version' => _PS_VERSION_,
            'module_dir' => $this->_path
        ));

        return $this->display(__FILE__, $tpl);
    }

    /**
     * Hak do kroku płatności zwrotnej/potwierdzenia zamówienia
     *
     * @param $params
     * @return bool|void
     */
    public function hookPaymentReturn($params) {
        // @todo: zastanowić się nad jego wykorzystaniem
    }

    /**
     * Waliduje zgodność otrzymanego XML'a
     * @param XML $response
     * @return boolen 
     */
    public function _validAllTransaction($response) {

        $service_id = Configuration::get($this->name_upper . '_SERVICE_PARTNER_ID');
        $shared_key = Configuration::get($this->name_upper . '_SHARED_KEY');
        if ($service_id != $response->serviceID)
            return false;

        $this->_checkHashArray = [];
        $hash = (string) $response->hash;
        $this->_checkHashArray[] = (string) $response->serviceID;

        foreach ($response->transactions->transaction as $trans) {
            $this->_checkInList($trans);
        }
        $this->_checkHashArray[] = $shared_key;
        return hash(HASH_ALGORITHM, implode(HASH_SEPARATOR, $this->_checkHashArray)) == $hash;
    }

    private function _checkInList($list) {
        foreach ((array) $list as $row) {
            if (is_object($row)) {
                $this->_checkInList($row);
            } else {
                $this->_checkHashArray[] = $row;
            }
        }
    }

    /**
     * Generuje i zwraca klucz hash na podstawie wartości pól z tablicy
     *
     * @param array $data
     * @return string
     */
    public function generateAndReturnHash($data) {
        $values_array = array_values($data);

        $values_array_filter = array_filter($values_array);

        $comma_separated = implode(",", $values_array_filter);

        $replaced = str_replace(",", HASH_SEPARATOR, $comma_separated);

        $hash = hash(HASH_ALGORITHM, $replaced);

        return $hash;
    }

    /**
     * Zwraca adres bramki
     *
     * @return string
     */
    public function getUrlGateway() {
        // Aktywny tryb usługi
        $mode = Configuration::get($this->name_upper . '_TEST_MODE');

        if ($mode) {
            return TEST_ADDRESS_URL;
        }
        return PROD_ADDRESS_URL;
    }

    /**
     * Haczyk dla nagłówków stron
     */
    public function hookHeader() {
        $this->context->controller->addCSS($this->_path . '/css/front.css');
    }

    /**
     * Potwierdzenie w postaci xml o prawidłowej/nieprawidłowej transakcji
     *
     * @param string $order_id
     * @param string $confirmation
     *
     * @return XML
     */
    protected function returnConfirmation($order_id, $confirmation) {
        // Id serwisu partnera
        $service_id = Configuration::get($this->name_upper . '_SERVICE_PARTNER_ID');

        // Klucz współdzielony
        $shared_key = Configuration::get($this->name_upper . '_SHARED_KEY');

        // Tablica danych z których wygenerować hash
        $hash_data = array($service_id, $order_id, $confirmation, $shared_key);

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

        $dom_order_id = $dom->createElement('orderID', $order_id);
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
     * @param array $transactions
     * @param string $hash
     */
    public function processStatusPayment($response) {
        // Kompatybilność wstecz dla wersji 1.4
        if (_PS_VERSION_ < '1.5') {
            $logger = new Logger();
        } else {
            $logger = new PrestaShopLogger();
        }

        if ($this->_validAllTransaction($response)) {
            $transaction_xml = $response->transactions->transaction;
            // Aktualizacja statusu zamówienia i transakcji
            $this->updateStatusTransactionAndOrder($transaction_xml);
            
        } else {
            $message = $this->name_upper . ' - Invalid hash: ' . $response->hash;
            // Potwierdzenie zwrotne o transakcji nie autentycznej
            $transaction_xml = $response->transactions->transaction;
            $logger->addLog($message, 3, null, 'Order', $transaction_xml->orderID);
            $this->returnConfirmation($transaction_xml->orderID, self::TRANSACTION_NOTCONFIRMED);
        }
    }

    /**
     * Sprawdza czy zamówienie zostało anulowane
     *
     * @param object $order
     *
     * @return boolean
     */
    public function isOrderCompleted($order) {
        $status = $order->getCurrentState();
        $stateOrderTab = array(6);

        return in_array($status, $stateOrderTab);
    }

    /**
     * Aktualizacja statusu zamówienia, transakcji oraz wysyłka maila do klienta
     *
     * @param $transaction
     * @throws Exception
     */
    protected function updateStatusTransactionAndOrder($transaction) {
        // Identyfikatory statusów płatności
        
        $status_accept_pay_id = Configuration::get($this->name_upper . '_STATUS_ACCEPT_PAY_ID');
        $status_waiting_pay_id = Configuration::get($this->name_upper . '_STATUS_WAIT_PAY_ID');
        $status_error_pay_id = Configuration::get($this->name_upper . '_STATUS_ERROR_PAY_ID');

        // Status płatności
        $payment_status = (string) $transaction->paymentStatus;

        // Id transakcji nadany przez bramkę
        $remote_id = (string)$transaction->remoteID;

        // Id zamówienia
        $order_id = (string)$transaction->orderID;

        // Objekt zamówienia
        $order = new OrderCore($order_id);
        //var_dump($order->getOrderPaymentCollection());
        // Kompatybilność wstecz dla wersji 1.4
        if (_PS_VERSION_ < '1.5') {
            $logger = new Logger();
            // Obiekt płatności zamówienia
            $order_payment = new PaymentCC();
        } else {
            // Obiekt płatności zamówienia
            $order_payments = $order->getOrderPaymentCollection();
            if (count($order_payments) > 0) {
                $order_payment = $order_payments[0];
            } else {
                $order_payment = new OrderPaymentCore();
            }
            $logger = new PrestaShopLogger();
        }

        if (!Validate::isLoadedObject($order)) {
            $message = $this->name_upper . ' - Order not found';
            $logger->addLog($message, 3, null, 'Order', $order_id);
            $this->returnConfirmation($order_id, self::TRANSACTION_NOTCONFIRMED);
            return;
        }

        if (!is_object($order_payment)) {
            $message = $this->name_upper . ' - Order payment not found';
            $logger->addLog($message, 3, null, 'OrderPayment', $order_id);
            $this->returnConfirmation($order_id, self::TRANSACTION_NOTCONFIRMED);
            return;
        }

        // Suma zamówienia
        $total_paid = $order->total_paid;
        $amount = number_format(round($total_paid, 2), 2, '.', '');
        // Jeśli zamówienie jest otwarte i status zamówienia jest różny od pustej wartości
        if (!($this->isOrderCompleted($order)) && $payment_status != '') {
            switch ($payment_status) {
                // Jeśli transakcja została rozpoczęta
                case self::PAYMENT_STATUS_PENDING:
                    // Jeśli aktualny status zamówienia jest różny od ustawionego jako "oczekiwanie na płatność"
                    if ($order->current_state != $status_waiting_pay_id) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->id_order_state = $status_waiting_pay_id;
                        $new_history->addWithemail(true);
                    }
                    break;
                // Jeśli transakcja została zakończona poprawnie
                case self::PAYMENT_STATUS_SUCCESS:
                    if ($order->current_state == $status_waiting_pay_id){
                        if (count($order_payments) > 0) {
                            $order_payment->amount = $amount;
                            $order_payment->transaction_id = $remote_id;
                            $order_payment->update();
                        } elseif (is_object($order_payment)) {
                            $order_payment->order_reference = $order->reference;
                            $order_payment->id_currency = $order->id_currency;
                            $order_payment->payment_method = $this->displayName;
                            $order_payment->amount = $amount;
                            $order_payment->transaction_id = $remote_id;
                            $order_payment->add();
                        }
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->id_order_state = $status_accept_pay_id;
                        $new_history->addWithemail(true);
                    }
                    break;
                // Jeśli transakcja nie została zakończona poprawnie
                case self::PAYMENT_STATUS_FAILURE:
                    // Jeśli aktualny status zamówienia jest równy ustawionemu jako "oczekiwanie na płatność"
                    if ($order->current_state == $status_waiting_pay_id) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = $order_id;
                        $new_history->id_order_state = $status_error_pay_id;
                        $new_history->addWithemail(true);
                    }
                    break;
                default:
                    break;
            }
            $this->returnConfirmation($order_id, self::TRANSACTION_CONFIRMED);  
        } else {
            $message = $this->name_upper . ' - Order status is cancel or payment status unknown';
            $logger->addLog($message, 3, null, 'OrderState', $order_id);
            $this->returnConfirmation($order_id, $message);
        }
    }

    private function displayForm() {
        // Opcje wyboru statusu oczekującego
        $options_waiting_status = '';

        // Opcje wyboru statusu prawidłowego
        $options_accept_status = '';

        // Opcje wyboru statusu nieprawidłowego
        $options_error_status = '';

        // Domyślny język
        $id_default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Dostępne statusy
        $statuses = OrderState::getOrderStates($id_default_lang);

        foreach ($statuses as $status) {
            $options_waiting_status .= '<option value="' . $status['id_order_state'] . '"'
                    . (Configuration::get($this->name_upper . '_STATUS_WAIT_PAY_ID') == $status['id_order_state'] ? 'selected="selected"' : '' ) . '>'
                    . $status['name']
                    . '</option>';
            $options_accept_status .= '<option value="' . $status['id_order_state'] . '"'
                    . (Configuration::get($this->name_upper . '_STATUS_ACCEPT_PAY_ID') == $status['id_order_state'] ? 'selected="selected"' : '' ) . '>'
                    . $status['name']
                    . '</option>';
            $options_error_status .= '<option value="' . $status['id_order_state'] . '"'
                    . (Configuration::get($this->name_upper . '_STATUS_ERROR_PAY_ID') == $status['id_order_state'] ? 'selected="selected"' : '' ) . '>'
                    . $status['name']
                    . '</option>';
        }

        $this->html .= '<h2>' . $this->displayName . '</h2>';
        $this->html .= '<form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
			<fieldset>
			<legend><img src="../img/admin/tab-preferences.gif" />' . $this->l('Settings') . '</legend>
				<table border="0" cellpadding="5" cellspacing="5" id="form">
					<tr>
						<td style="text-align: right;">' . $this->l('Test mode') . '</td>
						<td>
						    <select name="' . $this->name_upper . '_TEST_MODE">
						        <option value="1"'
                . (Configuration::get($this->name_upper . '_TEST_MODE') == 1 ? 'selected="selected"' : '' )
                . '>' . $this->l('Yes') . '</option>
						        <option value="0"'
                . (Configuration::get($this->name_upper . '_TEST_MODE') == 0 ? 'selected="selected"' : '' )
                . '>' . $this->l('No') . '</option>
                            </select>
						</td>
					</tr>
					<tr>
					    <td style="text-align: right;">' . $this->l('Service partner ID') . '</td>
					    <td>
					        <input type="text" name="' . $this->name_upper . '_SERVICE_PARTNER_ID"
					        value="' . htmlentities(Tools::getValue($this->name_upper . '_SERVICE_PARTNER_ID', Configuration::get($this->name_upper . '_SERVICE_PARTNER_ID')), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" />
					    </td>
                    </tr>
					<tr>
					    <td style="text-align: right;">' . $this->l('Shared key') . '</td>
					    <td>
					        <input type="text" name="' . $this->name_upper . '_SHARED_KEY"
					        value="' . htmlentities(Tools::getValue($this->name_upper . '_SHARED_KEY', Configuration::get($this->name_upper . '_SHARED_KEY')), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" />
					    </td>
                    </tr>
					<tr>
						<td style="text-align: right;">' . $this->l('Status waiting payment') . '</td>
						<td>
						    <select name="' . $this->name_upper . '_STATUS_WAIT_PAY_ID">
						        ' . $options_waiting_status . '
                            </select>
						</td>
					</tr>
					<tr>
						<td style="text-align: right;">' . $this->l('Status accept payment') . '</td>
						<td>
						    <select name="' . $this->name_upper . '_STATUS_ACCEPT_PAY_ID">
						        ' . $options_accept_status . '
                            </select>
						</td>
                    </tr>
					</tr>
						<td style="text-align: right;">' . $this->l('Status error payment') . '</td>
						<td>
						    <select name="' . $this->name_upper . '_STATUS_ERROR_PAY_ID">
						        ' . $options_error_status . '
                            </select>
						</td>
					</tr>
					<tr><td colspan="2" align="center"><input class="button" name="submit' . $this->name . '" value="' . $this->l('Save') . '" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';

        return $this->html;
    }

    /**
     * Pobiera dane z tablicy POST i zapisuje je do tabeli konfiguracyjnej
     *
     */
    private function postProcess() {
        if (Tools::isSubmit('submit' . $this->name)) {
            unset($_POST['submitbluepayment']);
            foreach ($_POST as $key => $val) {
                Configuration::updateValue($key, $val);
            }
            $this->html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
        }
    }

    /**
     * Zwraca uri path dla modułu
     *
     * @return string
     */
    public function getPathUri() {
        return $this->_path;
    }

}

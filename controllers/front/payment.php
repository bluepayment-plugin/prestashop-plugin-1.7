<?php
/**
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2016
 * @license        https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
 */

class BluePaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        // Identyfikator koszyka
        $cart_id = $cart->id;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
            Tools::redirect('index.php?controller=order&step=1');

        // Sprawdzenie czy opcja płatności jest nadal aktywna w przypadku kiedy klient dokona zmiany adresu
        // przed finalizacją zamówienia
        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
            if ($module['name'] == 'bluepayment')
            {
                $authorized = true;
                break;
            }

        if (!$authorized)
            die($this->module->l('This payment method is not available.', 'bluepayment'));

        // Stworzenie obiektu klienta na podstawie danych z koszyka
        $customer = new Customer($cart->id_customer);

        // Jeśli nie udało się stworzyć i załadować obiektu klient, przekieruj na 1 krok
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        // Całkowita suma zamówienia
        $total_paid = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $amount = number_format(round($total_paid, 2), 2, '.', '');


        // Id statusu zamówienia
        $id_order_state = Configuration::get($this->module->name_upper.'_STATUS_WAIT_PAY_ID');

        // Walidacja zamówienia
        $this->module->validateOrder($cart_id, $id_order_state, $amount, $this->module->displayName,
            NULL, array(), null, false, $customer->secure_key);

        // Idenfyfikator zamówienia
        $order_id = $this->module->currentOrder;

        // Adres bramki
        $form_url = $this->module->getUrlGateway();

        // Identyfikator serwisu partnera
        $service_id = Configuration::get($this->module->name_upper.'_SERVICE_PARTNER_ID');

        // Adres email klienta
        $customer_email = $customer->email;

        // Klucz współdzielony
        $shared_key = Configuration::get($this->module->name_upper.'_SHARED_KEY');

        // Parametry dla klucza hash

        $gateway_id = false;

        if (isset($this->context->cookie->gateway_id)){
            $gateway_id = $this->context->cookie->gateway_id;
        }
        $this->context->cookie->gateway_id = false;

        if ($gateway_id !== false){
            $hash_data = array($service_id, $order_id, $amount, $gateway_id, $customer_email, $shared_key);
        } else {
            $hash_data = array($service_id, $order_id, $amount, $customer_email, $shared_key);
        }
        // Klucz hash
        $hash_local = $this->module->generateAndReturnHash($hash_data);

        // Parametry dla formularza wysyłane do bramki
        if ($gateway_id !== false){
            $params = array(
                'ServiceID' => $service_id,
                'OrderID' => $order_id,
                'Amount' => $amount,
                'GatewayID' => $gateway_id,
                'CustomerEmail' => $customer_email,
                'Hash' => $hash_local
            );
        } else {
            $params = array(
                'ServiceID' => $service_id,
                'OrderID' => $order_id,
                'Amount' => $amount,
                'CustomerEmail' => $customer_email,
                'Hash' => $hash_local
            );
        }

        $this->context->smarty->assign(array(
            'params' => $params,
            'module_dir' => $this->module->getPathUri(),
            'form_url' => $form_url,
        ));

        $this->setTemplate("module:bluepayment/views/templates/front/payment.tpl");

    }

}
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

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(_PS_MODULE_DIR_.'bluepayment/bluepayment.php');

class PaymentController extends FrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function setMedia()
    {
        parent::setMedia();
    }

    public function displayContent()
    {
        parent::displayContent();
        $context = Context::getContext();

        $bp = new BluePayment();

        $cart = $context->cart;

        // Identyfikator koszyka
        $cart_id = $cart->id;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$bp->active)
            Tools::redirect('/order?step=0');

        // Sprawdzenie czy opcja płatności jest nadal aktywna w przypadku kiedy klient dokona zmiany adresu
        // przed finalizacją zamówienia
        $authorized = false;
        $modules = Module::getPaymentModules();
        foreach ($modules as $module)
            if ($module['name'] == $bp->name)
            {
                $authorized = true;
                break;
            }

        if (!$authorized)
            die($bp->l('This payment method is not available.', $bp->name));

        // Stworzenie obiektu klienta na podstawie danych z koszyka
        $customer = new Customer($cart->id_customer);

        // Jeśli nie udało się stworzyć i załadować obiektu klient, przekieruj na 1 krok
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('/order?step=0');

        // Całkowita suma zamówienia
        $total_paid = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $amount = number_format(round($total_paid, 2), 2, '.', '');

        // Id statusu zamówienia
        $id_order_state = Configuration::get($bp->name_upper.'_STATUS_WAIT_PAY_ID');

        // Walidacja zamówienia
        $bp->validateOrder($cart_id, $id_order_state, $amount, $bp->displayName,
            NULL, array(), null, false, $customer->secure_key);

        // Idenfyfikator zamówienia
        $order_id = $bp->currentOrder;

        // Adres bramki
        $form_url = $bp->getUrlGateway();

        // Identyfikator serwisu partnera
        $service_id = Configuration::get($bp->name_upper.'_SERVICE_PARTNER_ID');

        // Adres email klienta
        $customer_email = $customer->email;

        // Klucz współdzielony
        $shared_key = Configuration::get($bp->name_upper.'_SHARED_KEY');

        // Parametry dla klucza hash
        $hash_data = array($service_id, $order_id, $amount, $customer_email, $shared_key);

        // Klucz hash
        $hash_local = $bp->generateAndReturnHash($hash_data);

        // Parametry dla formularza wysyłane do bramki
        $params = array(
            'ServiceID' => $service_id,
            'OrderID' => $order_id,
            'Amount' => $amount,
            'CustomerEmail' => $customer_email,
            'Hash' => $hash_local
        );

        $context->smarty->assign(array(
            'params' => $params,
            'module_dir' => $bp->getPathUri(),
            'form_url' => $form_url,
        ));

        $context->smarty->display(_PS_MODULE_DIR_.$bp->name.'/views/templates/front/payment.tpl');
    }
}

$paymentController = new PaymentController();
$paymentController->run();
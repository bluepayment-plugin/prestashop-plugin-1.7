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

class BluePaymentBackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Id serwisu partnera
        $service_id = Configuration::get($this->module->name_upper . '_SERVICE_PARTNER_ID');

        // Id zamówienia
        $order_id = Tools::getValue('OrderID');

        // Klucz współdzielony
        $shared_key = Configuration::get($this->module->name_upper . '_SHARED_KEY');

        // Hash
        $hash = Tools::getValue('Hash');

        // Tablica danych z których wygenerować hash
        $hash_data = array($service_id, $order_id, $shared_key);

        $hash_local = $this->module->generateAndReturnHash($hash_data);

        // Jeśli klucz hash jest prawidłowy przekieruj na stronę zamówień
        if ($hash == $hash_local)
            Tools::redirect('index.php?controller=order-confirmation&id_module='.$this->module->id.'&id_order='.$order_id);

        $this->context->smarty->assign(array(
                'hash_valid' => false
            )
        );

        $this->setTemplate("payment_return.tpl");
    }

}
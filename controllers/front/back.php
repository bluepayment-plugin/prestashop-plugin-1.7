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


class BluePaymentBackModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        $this->page_name = 'opinion'; // page_name and body id
        $this->display_column_left = true;
        $this->display_column_right = true;
        parent::init();
    }

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

        $valid = ($hash == $hash_local);

        if ($valid && $this->context->customer->isLogged())
            Tools::redirect('index.php?controller=order-confirmation&id_module='.$this->module->id.'&id_order='.$order_id);

        $this->context->smarty->assign(array(
                'hash_valid' => $valid,
                'order' => new OrderCore($order_id)
            )
        );

        $this->setTemplate("payment_return.tpl");
    }

}
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
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(_PS_MODULE_DIR_.'bluepayment/bluepayment.php');

class BackController extends FrontController
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

        // Id serwisu partnera
        $service_id = Configuration::get($bp->name_upper . '_SERVICE_PARTNER_ID');

        // Id zamówienia
        $order_id = Tools::getValue('OrderID');

        // Klucz współdzielony
        $shared_key = Configuration::get($bp->name_upper . '_SHARED_KEY');

        // Hash
        $hash = Tools::getValue('Hash');

        // Tablica danych z których wygenerować hash
        $hash_data = array($service_id, $order_id, $shared_key);

        $hash_local = $bp->generateAndReturnHash($hash_data);

        // Jeśli klucz hash jest prawidłowy przekieruj na stronę zamówień
        if ($hash == $hash_local)
            Tools::redirect('order-history');

        $context->smarty->assign(array(
                'hash_valid' => false
            )
        );

        $context->smarty->display(_PS_MODULE_DIR_.$bp->name.'/views/templates/front/payment_return.tpl');
    }
}

$backController = new BackController();
$backController->run();
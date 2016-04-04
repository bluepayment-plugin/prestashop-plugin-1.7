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

class BluePaymentStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Parametry z request
        $param_transactions = Tools::getValue('transactions');

        // Jeśli parametr 'transactions' istnieje i zawiera przynajmniej jedną transakcję
        if ($param_transactions != '')
        {
            // Odkodowanie parametru transakcji
            $base64transactions = base64_decode($param_transactions);

            // Odczytanie parametrów z xml-a
            $simple_xml = simplexml_load_string($base64transactions);

            $this->module->processStatusPayment($simple_xml);
        }
        exit;
    }
}
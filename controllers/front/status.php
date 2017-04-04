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


class BluePaymentStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Parametry z request
        $param_transactions = Tools::getValue('transactions');

        // Jeśli parametr 'transactions' istnieje i zawiera przynajmniej jedną transakcję
        if ($param_transactions != '')
        {
            header("Content-type: text/xml");
            // Odkodowanie parametru transakcji
            $base64transactions = base64_decode($param_transactions);

            // Odczytanie parametrów z xml-a
            $simple_xml = simplexml_load_string($base64transactions);

            $this->module->processStatusPayment($simple_xml);
        }
        exit;
    }
}
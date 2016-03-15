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

class StatusController extends FrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function displayContent()
    {
        parent::displayContent();

        $bp = new BluePayment();

        // Parametry z request
        $param_transactions = Tools::getValue('transactions');

        // Jeśli parametr 'transactions' istnieje i zawiera przynajmniej jedną transakcję
        if (isset($param_transactions))
        {
            // Odkodowanie parametru transakcji
            $base64transactions = base64_decode($param_transactions);

            // Odczytanie parametrów z xml-a
            $simple_xml = simplexml_load_string($base64transactions);

            if(is_object($simple_xml))
            {
                // Lista transakcji
                $transactions = $simple_xml->transactions;

                // Klucz hash
                $hash = $simple_xml->hash;

                // Jeśli istnieją transakcje
                if (count($transactions) > 0)
                {
                    $bp->processStatusPayment($transactions, $hash);
                }
            }
        }
    }
}

$statusController = new StatusController();
$statusController->init();
$statusController->preProcess();
$statusController->displayContent();
$statusController->process();
exit;
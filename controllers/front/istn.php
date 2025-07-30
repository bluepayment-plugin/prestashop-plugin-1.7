<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use BlueMedia\OnlinePayments\Gateway;
use BluePayment\Service\Istn as IstnService;

class BluePaymentIstnModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        header('Content-type: text/xml');

        file_put_contents(__DIR__.'/../../logs/istn.xml', "\n".json_encode($_REQUEST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), FILE_APPEND);
        file_put_contents(__DIR__.'/../../logs/istn.xml', "\n".json_encode(base64_decode($_REQUEST['transactions']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), FILE_APPEND);

        $istnService = new IstnService($this->module);
        $serviceID = null;
        $processedTransactions = [];
        $isAuthentic = false;

        try {
            $xml = Gateway::getItnInXml();

            if ($xml === false || !isset($xml->serviceID) || !isset($xml->hash) || !isset($xml->transactions)) {
                PrestaShopLogger::addLog('Autopay ISTN Controller: Failed to parse XML or missing crucial elements.', 3);
                $serviceID = ($xml && isset($xml->serviceID)) ? (string)$xml->serviceID : null;
                echo $istnService->returnConfirmation($serviceID, $processedTransactions, $isAuthentic);
                exit;
            }

            $result = $istnService->processIstnRequest($xml);

            $serviceID = $result['serviceID'];
            $processedTransactions = $result['processedTransactions'];
            $isAuthentic = $result['authentic'];

            echo $istnService->returnConfirmation($serviceID, $processedTransactions, $isAuthentic);

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Autopay ISTN Controller: General Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 3);
            echo $istnService->returnConfirmation($serviceID, $processedTransactions, $isAuthentic);
        }
        exit;
    }
}

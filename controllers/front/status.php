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

use BluePayment\Service\Transactions;

class BluePaymentStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        header('Content-type: text/xml');

        try {
            Db::getInstance()->execute('START TRANSACTION;');

            $transaction = new Transactions(
                $this->module,
                new OrderHistory()
            );
            $transaction->processStatusPayment(\BlueMedia\OnlinePayments\Gateway::getItnInXml());
            Db::getInstance()->execute('COMMIT;');
        } catch (Exception $exception) {
            Tools::redirect($this->context->link->getModuleLink('bluepayment', 'paymentStatus', [
                'error' => 'Payment error (' . print_r($exception) . ')',
            ], true));
        }
        exit;
    }
}

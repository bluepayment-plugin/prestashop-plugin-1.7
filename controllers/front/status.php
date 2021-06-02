<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

/**
 * @property BluePayment $module
 */
class BluePaymentStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        require_once __DIR__ . '/../../sdk/index.php';

        header('Content-type: text/xml');
        $this->module->processStatusPayment(\BlueMedia\OnlinePayments\Gateway::getItnInXml());

        exit;
    }
}

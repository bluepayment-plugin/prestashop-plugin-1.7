<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
use BluePayment\Config\Config;
class BluePaymentBackModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $orderId = Tools::getValue('OrderID');
        $order = explode('-', $orderId)[0];
        $order = new OrderCore($order);
        $customer = new CustomerCore($order->id_customer);
        $query = new \DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = ' . (int) $orderId)
            ->select('*');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
//echo $order->id_customer; die();
        if($result['gateway_id'] == Config::GATEWAY_ID_APC){
            $this->context->customer = new Customer($order->id_customer);
            
//            $cust = new Customer($order->id_customer);
//            $this->context->updateCustomer($cust);
        }
//        die();
        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='.$order->id_cart.'&id_module='.$this->module->id
            .'&id_order='.$order->id.'&key='.$customer->secure_key
        );
    }
}

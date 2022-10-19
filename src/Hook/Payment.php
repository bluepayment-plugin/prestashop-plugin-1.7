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

declare(strict_types=1);

namespace BluePayment\Hook;

use Configuration as Cfg;
use DbQuery;
use Db;
use Shop;
use BluePayment\Service\FactoryPaymentMethods;
use BluePayment\Statuses\OrderStatusMessageDictionary;
use BluePayment\Until\Helper;

class Payment extends AbstractHook
{
    const AVAILABLE_HOOKS = [
        'paymentReturn',
        'orderConfirmation',
        'actionObjectOrderHistoryAddAfter'
    ];




    /**
     * Return payment/order confirmation step hook
     * @param $params
     * @return string|void
     */
    public function paymentReturn($params)
    {
        if (!$this->module->active ||
            !isset($params['order']) ||
            ($params['order']->module != $this->module->name)) {
            return null;
        }

        $currency = new \Currency($params['order']->id_currency);

        $orderData = $this->getDataToOrderResults(
            $params,
            $currency
        );

        if(!$orderData) {
            return;
        }

        return $this->module->fetch('module:bluepayment/views/templates/hook/paymentReturn.tpl');
    }


    public function getDataToOrderResults($params, $currency): bool
    {
        $products = [];

        if (!empty($params['order']->getProducts())) {
            foreach ($params['order']->getProducts() as $product) {
                $cat = new \Category($product['id_category_default'], $this->context->language->id);

                $newProduct = new \stdClass();
                $newProduct->name = $product['product_name'];
                $newProduct->category = $cat->name;
                $newProduct->price = $product['price'];
                $newProduct->quantity = $product['product_quantity'];
                $newProduct->sku = $product['product_reference'];

                $products[] = $newProduct;
            }
        } else {
            return false;
        }

        $this->context->smarty->assign([
            'order_id' => $params['order']->id,
            'shop_name' => $this->context->shop->name,
            'revenue' => $params['order']->total_paid,
            'shipping' => $params['order']->total_shipping,
            'tax' => $params['order']->carrier_tax_rate,
            'currency' => $currency->iso_code,
            'products' => $products,
        ]);

        return true;
    }


    public function orderConfirmation($params)
    {
        if (!$params['order'] || !$params['order']->id) {
            return null;
        }

        $id_default_lang = (int)Cfg::get('PS_LANG_DEFAULT');
        $order = new \OrderCore($params['order']->id);
        $state = $order->getCurrentStateFull($id_default_lang);

        $orderStatusMessage = OrderStatusMessageDictionary::getMessage($state['id_order_state']) ?? $state['name'];

        $this->context->smarty->assign([
            'order_status' => $this->module->l($orderStatusMessage),
        ]);

        return $this->module->fetch('module:bluepayment/views/templates/hook/order-confirmation.tpl');
    }


    public function actionObjectOrderHistoryAddAfter($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.7.5', '<')) {
            // PrestaShop < 1.7.7.5
            $acceptedStates = [
                Cfg::get($this->module->name_upper . '_STATUS_ACCEPT_PAY_ID'),
                Cfg::get($this->module->name_upper . '_STATUS_ERROR_PAY_ID')
            ];
            if (in_array($params['object']->id_order_state, $acceptedStates)) {
                $order = new \Order($params['object']->id_order);
                $idHistoryState = Helper::getLastOrderState((int)$params['object']->id_order);
                Helper::sendEmail($order, [], $idHistoryState);
            }
        }
    }
}

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

declare(strict_types=1);

namespace BluePayment\Test\Processor;

use BluePayment\Test\Factory\TestObjectFactory;
use Order;

if (!defined('_PS_VERSION_')) {
    exit;
}
final class OrderProcessor
{
    /**
     * @var TestObjectFactory
     */
    private $testObjectFactory;

    /**
     * @var \Module
     */
    private $module;

    public function __construct(TestObjectFactory $testObjectFactory, \Module $module)
    {
        $this->testObjectFactory = $testObjectFactory;
        $this->module = $module;
    }

    /**
     * Create a new order using test objects
     *
     * @return array Order creation result with order ID and status
     *
     * @throws \PrestaShopException
     */
    public function createOrder(): array
    {
        $customer = $this->testObjectFactory->getOrCreateCustomer();
        $address = $this->testObjectFactory->getOrCreateAddress($customer);
        $product = $this->testObjectFactory->getOrCreateProduct();
        $carrier = $this->testObjectFactory->getDefaultCarrier();
        $cart = $this->testObjectFactory->createCart($customer, $address, $product, $carrier);

        $orderId = \Order::getIdByCartId((int) $cart->id);
        if ($orderId) {
            $order = new \Order($orderId);
            if (\Validate::isLoadedObject($order)) {
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'order' => $order,
                    'cart_id' => (int) $cart->id,
                    'message' => 'Order already exists for this cart',
                ];
            }
        }

        $currency = new \Currency((int) $cart->id_currency);
        if (!\Validate::isLoadedObject($currency)) {
            return [
                'success' => false,
                'error' => 'Invalid currency',
            ];
        }

        try {
            if (!method_exists($this->module, 'validateOrder')) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'error' => 'Module does not support validateOrder method',
                ];
            }

            $this->module->validateOrder(
                (int) $cart->id,
                (int) \Configuration::get('BLUEPAYMENT_STATUS_WAIT_PAY_ID'),
                (float) $cart->getOrderTotal(),
                $this->module->displayName,
                null,
                [],
                null,
                false,
                $customer->secure_key
            );

            $orderId = \Order::getIdByCartId((int) $cart->id);
            if (!$orderId) {
                return [
                    'success' => false,
                    'error' => 'Failed to create order',
                ];
            }

            $order = new \Order($orderId);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order' => $order,
                'cart_id' => (int) $cart->id,
                'message' => 'Order created successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

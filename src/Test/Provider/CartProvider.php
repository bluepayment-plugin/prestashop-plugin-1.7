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

namespace BluePayment\Test\Provider;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for test cart data
 */
class CartProvider
{
    /**
     * Prepare data for new test cart
     *
     * @param \Customer $customer Customer to associate cart with
     * @param \Address $address Address to associate cart with
     * @param \Carrier|null $carrier Carrier to use for delivery (or null to use default)
     *
     * @return array Cart data
     */
    public function prepareTestCartData(\Customer $customer, \Address $address, ?\Carrier $carrier): array
    {
        $context = \Context::getContext();
        $currencyId = (int) \Currency::getDefaultCurrency()->id;
        $langId = (int) $context->language->id;

        return [
            'id_customer' => (int) $customer->id,
            'id_address_delivery' => (int) $address->id,
            'id_address_invoice' => (int) $address->id,
            'id_currency' => $currencyId,
            'id_lang' => $langId,
            'id_carrier' => $carrier ? (int) $carrier->id : (int) \Configuration::get('PS_CARRIER_DEFAULT'),
        ];
    }

    /**
     * Add product to cart
     *
     * @param \Cart $cart Cart to update
     * @param int $productId Product ID to add
     * @param int $quantity Quantity to add
     *
     * @return bool Success status
     */
    public function addProductToCart(\Cart $cart, int $productId, int $quantity = 1): bool
    {
        return $cart->updateQty($quantity, $productId);
    }
}

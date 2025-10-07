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

namespace BluePayment\Test\Factory;

use Address;
use BluePayment\Test\Provider\AddressProvider;
use BluePayment\Test\Provider\CarrierProvider;
use BluePayment\Test\Provider\CartProvider;
use BluePayment\Test\Provider\CustomerProvider;
use BluePayment\Test\Provider\ProductProvider;
use Cart;
use Customer;
use Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Factory for creating test objects
 */
class TestObjectFactory
{
    /**
     * @var CustomerProvider
     */
    private $customerProvider;

    /**
     * @var AddressProvider
     */
    private $addressProvider;

    /**
     * @var ProductProvider
     */
    private $productProvider;

    /**
     * @var CartProvider
     */
    private $cartProvider;

    /**
     * @var CarrierProvider
     */
    private $carrierProvider;

    /**
     * TestObjectFactory constructor.
     *
     * @param CustomerProvider $customerProvider
     * @param AddressProvider $addressProvider
     * @param ProductProvider $productProvider
     * @param CartProvider $cartProvider
     */
    public function __construct(
        CustomerProvider $customerProvider,
        AddressProvider $addressProvider,
        ProductProvider $productProvider,
        CartProvider $cartProvider,
        CarrierProvider $carrierProvider
    ) {
        $this->customerProvider = $customerProvider;
        $this->addressProvider = $addressProvider;
        $this->productProvider = $productProvider;
        $this->cartProvider = $cartProvider;
        $this->carrierProvider = $carrierProvider;
    }

    /**
     * Get or create test customer
     *
     * @return \Customer Test customer
     */
    public function getOrCreateCustomer(): \Customer
    {
        $customer = $this->customerProvider->getTestCustomer();

        if (null === $customer) {
            $customerData = $this->customerProvider->prepareTestCustomerData();

            $customer = new \Customer();
            foreach ($customerData as $key => $value) {
                $customer->{$key} = $value;
            }
            $customer->save();

            $this->customerProvider->saveTestCustomerId((int) $customer->id);
        }

        return $customer;
    }

    /**
     * Get or create test address for customer
     *
     * @param \Customer $customer Customer to associate address with
     *
     * @return \Address Test address
     */
    public function getOrCreateAddress(\Customer $customer): \Address
    {
        $address = $this->addressProvider->getTestAddress();

        if (null === $address) {
            $addressData = $this->addressProvider->prepareTestAddressData($customer);

            $address = new \Address();
            foreach ($addressData as $key => $value) {
                $address->{$key} = $value;
            }
            $address->save();

            $this->addressProvider->saveTestAddressId((int) $address->id);
        }

        return $address;
    }

    /**
     * Get or create test product
     *
     * @return \Product Test product
     */
    public function getOrCreateProduct(): \Product
    {
        $product = $this->productProvider->getTestProduct();

        if (null === $product) {
            $productData = $this->productProvider->prepareTestProductData();

            $product = new \Product();
            foreach ($productData as $key => $value) {
                $product->{$key} = $value;
            }
            $product->save();

            $this->productProvider->setProductQuantity($product);
            $this->productProvider->addProductToShops($product);

            $this->productProvider->saveTestProductId((int) $product->id);
        }

        return $product;
    }

    /**
     * Create test cart with customer, address and product
     *
     * @param \Customer $customer Customer to associate cart with
     * @param \Address $address Address to associate cart with
     * @param \Product $product Product to add to cart
     * @param \Carrier|null $carrier Carrier to use for delivery (or null to use default)
     *
     * @return \Cart Test cart
     */
    public function createCart(\Customer $customer, \Address $address, \Product $product, ?\Carrier $carrier = null): \Cart
    {
        $cartData = $this->cartProvider->prepareTestCartData($customer, $address, $carrier);

        $cart = new \Cart();
        foreach ($cartData as $key => $value) {
            $cart->{$key} = $value;
        }
        $cart->save();

        $this->cartProvider->addProductToCart($cart, (int) $product->id);

        return $cart;
    }

    /**
     * Get default carrier
     *
     * @return \Carrier Default carrier
     *
     * @throws \BluePayment\Test\Exception\TestException When no valid carrier is found
     */
    public function getDefaultCarrier(): \Carrier
    {
        return $this->carrierProvider->getDefaultCarrier();
    }
}

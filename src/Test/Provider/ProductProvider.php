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

use BluePayment\Test\Config\Data\TestDataConfig;
use Configuration;
use Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for test product data
 */
class ProductProvider
{
    /**
     * Get test product if exists
     *
     * @return \Product|null Product object or null if not found
     */
    public function getTestProduct(): ?\Product
    {
        $productId = (int) \Configuration::get(TestDataConfig::TEST_PRODUCT_ID_KEY);

        if ($productId) {
            $product = new \Product($productId);

            if (\Validate::isLoadedObject($product)) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Save test product ID to configuration
     *
     * @param int $productId Product ID to save
     *
     * @return bool Success status
     */
    public function saveTestProductId(int $productId): bool
    {
        return \Configuration::updateValue(TestDataConfig::TEST_PRODUCT_ID_KEY, $productId);
    }

    /**
     * Prepare data for new test product
     *
     * @return array Product data
     */
    public function prepareTestProductData(): array
    {
        $context = \Context::getContext();
        $shopId = (int) $context->shop->id;
        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');

        return [
            'name' => [
                $defaultLangId => 'Test BLIK Product',
            ],
            'link_rewrite' => [
                $defaultLangId => 'test-blik-product',
            ],
            'description' => [
                $defaultLangId => 'Test product for BLIK transaction testing',
            ],
            'description_short' => [
                $defaultLangId => 'Test product for BLIK transaction testing',
            ],
            'active' => 0,
            'visibility' => 'none',
            'available_for_order' => 1,
            'price' => 1.23,
            'id_tax_rules_group' => 0,
            'id_shop_default' => $shopId,
            'id_category_default' => (int) \Configuration::get('PS_HOME_CATEGORY'),
        ];
    }

    /**
     * Set product quantity
     *
     * @param \Product $product Product to update
     * @param int $quantity Quantity to set
     *
     * @return bool|null Success status
     */
    public function setProductQuantity(\Product $product, int $quantity = 100): ?bool
    {
        return \StockAvailable::setQuantity($product->id, 0, $quantity);
    }

    /**
     * Add product to all shops
     *
     * @param \Product $product Product to add to shops
     *
     * @return bool Success status
     */
    public function addProductToShops(\Product $product): bool
    {
        $shops = \Shop::getShops(true);
        $shopIds = [];
        foreach ($shops as $shop) {
            $shopIds[] = (int) $shop['id_shop'];
        }

        $product->associateTo($shopIds);

        return true;
    }
}

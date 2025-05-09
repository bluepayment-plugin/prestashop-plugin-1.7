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

namespace BlueMedia\ProductFeed\Presenter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductPresenter
{
    public function present(array &$products, $idLang, $idShop)
    {
        foreach ($products as &$product) {
            $idDefaultCurrency = \Configuration::get('PS_CURRENCY_DEFAULT', null, null, $idShop);

            $price = \Product::getPriceStatic(
                (int) $product['id_product'],
                true,
                isset($product['id_product_attribute']) && $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null
            );

            $currency = new \Currency($idDefaultCurrency);
            $product['price'] = \Tools::ps_round(\Tools::convertPrice($price, $currency->id), 2) . ' ' . $currency->iso_code;

            $product['title'] = \Product::getProductName(
                (int) $product['id_product'],
                isset($product['id_product_attribute']) && $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null,
                $idLang
            );

            $product['condition'] = 'new';
            $product['quantity'] = \StockAvailable::getQuantityAvailableByProduct(
                (int) $product['id_product'],
                isset($product['id_product_attribute']) && $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null,
                $idShop
            );
            $product['link'] = \Context::getContext()->link->getProductLink(
                (int) $product['id_product'],
                \Tools::link_rewrite($product['title']),
                null,
                null,
                $idLang,
                $idShop,
                isset($product['id_product_attribute']) && $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null
            );
            $cover = \Product::getCover((int) $product['id_product']);

            if (isset($cover['id_image'])) {
                $product['image_link'] = \Context::getContext()->link->getImageLink(
                    \Tools::link_rewrite($product['title']),
                    $cover['id_image']
                );
            }
        }
    }
}

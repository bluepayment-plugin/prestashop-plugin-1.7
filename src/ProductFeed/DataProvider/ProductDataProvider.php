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

namespace BlueMedia\ProductFeed\DataProvider;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductDataProvider
{
    public function getProduct(int $idShop, int $idLang): ?array
    {
        $q = new \DbQuery();
        $q->select('p.id_product, pl.name, pl.description');
        $q->from('product', 'p');
        $q->innerJoin('product_shop',
            'ps',
            'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop);
        $q->innerJoin('product_lang',
            'pl',
            'ps.id_product = pl.id_product AND ps.id_shop = ' . $idShop . ' AND pl.id_lang = ' . $idLang);

        return \Db::getInstance()->executeS($q);
    }
}

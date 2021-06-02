{*
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2020
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
<script type="text/javascript">
    ga('require', 'ecommerce');

    ga('ecommerce:addTransaction', {
        'id': '{$order_id}',
        'affiliation': '{$shop_name}',
        'revenue': '{$revenue}',
        'shipping': '{$shipping}',
        'tax': '{$tax}',
        'currency': '{$currency}'
    });

    {foreach from=$products item=product}
    ga('ecommerce:addItem', {
        'id': '{$order_id}',
        'name': '{$product->name}',
        'sku': '{$product->sku}',
        'category': '{$product->category}',
        'price': '{$product->price}',
        'quantity': '{$product->quantity}'
    });
    {/foreach}


    ga('ecommerce:send');
</script>

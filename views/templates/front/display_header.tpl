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
 * @copyright      Copyright (c) 2015-2026
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}

{if isset($is_disable_product_feed) && $is_disable_product_feed}
	<script src="https://plugins-api.autopay.pl/dokumenty/autopay-pixel.js?ecommerce={$ecommerce|escape:'url':'UTF-8'}&amp;ecommerce_version={$ecommerce_version|escape:'url':'UTF-8'}&programming_language_version={$programming_language_version|escape:'url':'UTF-8'}&plugin_name={$plugin_name|escape:'url':'UTF-8'}&plugin_version={$plugin_version|escape:'url':'UTF-8'}&service_id={$service_id|escape:'url':'UTF-8'}"></script>
{/if}
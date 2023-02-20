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
 * @copyright      Copyright (c) 2015-2022
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
<div class="autopay-container"
     data-uid="{$userId}"
     data-cart_id="{$cartId}"
     data-currency="{$currency}"
     data-currency_iso="{$currency_iso}"
     data-action="{$base}apc/V1/autopay/add-to-cart"
     data-minimum_order_value="{$minimum_order_value}"
     data-apc_merchantid="{$apc_merchantid}"
     data-apc_button_theme="{$apc_button_theme}"
     data-apc_button_fullwidth="{$apc_button_fullwidth}"
     data-apc_button_rounded="{$apc_button_rounded}"
     data-apc_button_margintop="{$apc_button_margintop}"
     data-apc_button_marginbottom="{$apc_button_marginbottom}"
     data-id_product="{$id_product}"
     data-id_product_attribute="{$id_product_attribute}" style="margin-top:{$apc_button_margintop}px; margin-bottom: {$apc_button_marginbottom}px"></div>
{include file="module:bluepayment/views/templates/hook/_partials/autopay-modal.tpl"}

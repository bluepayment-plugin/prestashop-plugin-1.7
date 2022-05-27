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
<!-- Global site tag (gtag.js) - Google Analytics -->
{literal}<script async src="https://www.googletagmanager.com/gtag/js?id={/literal}{$tracking_id}{literal}"></script>{/literal}
{literal}<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '{/literal}{$tracking_id}{literal}');
</script>{/literal}
<!-- END Global site tag (gtag.js) - Google Analytics -->

{if isset($bm_ajax_controller)}
	<script type="text/javascript">
		var bm_ajax_controller = "{$bm_ajax_controller}";
	</script>
{/if}

{if isset($controller) && $controller == 'cart'}
	<script type="text/javascript">

		$(document).ready(function () {
			$("body").on("click", ".cart-detailed-actions .btn", function () {

                {literal}
				gtag('event', 'begin_checkout', {
					"items": [
                        {/literal}
                        {foreach from=$products item=product}
                            {literal}{{/literal}
	                        "id": "{$product['id_product']|escape:'htmlall':'UTF-8'}",
							"name": "{$product['name'] nofilter}",
							"brand": "{$product['manufacturer_name'] nofilter}",
							"category": "{$product['category'] nofilter}",
							"variant": "{$product['id_product_attribute']}",
							"quantity": "{$product['cart_quantity']|escape:'htmlall':'UTF-8'}",
							"price": "{$product['price']|escape:'htmlall':'UTF-8'}",
                            {literal}},{/literal}
                        {/foreach}
                        {literal}
					],{/literal}
                    {literal}
				});
                {/literal}
			});
		});

	</script>
{elseif isset($controller) && $controller == 'product' && isset($product)}
	<script type="text/javascript">
        {literal}
		gtag('event', 'view_item', {
			"items": [
				{{/literal}
					"id": "{$product['id_product']|escape:'htmlall':'UTF-8'}",
					"name": "{$product['name'] nofilter}",
					{if isset($product['manufacturer_name'])}
						"brand": "{$product['manufacturer_name']|implode:','|escape:'html':'UTF-8'}",
					{/if}
					"category": "{$product['category'] nofilter}",
					"variant": "{$product['id_product_attribute']|escape:'htmlall':'UTF-8'}",
					"price": "{$product['price']|escape:'htmlall':'UTF-8'}",
                {literal}}
			],{/literal}
            {literal}
		});
        {/literal}
	</script>
{/if}

{literal}
<script type="text/javascript">
	$(document).ready(function () {

		if(typeof prestashop !== 'undefined') {
			prestashop.on('updateCart', function (event) {
				if (event && event.reason) {
					if(event.reason.linkAction === 'delete-from-cart') {
						bmEventRemoveProduct(
							event.reason.idProduct,
							event.reason.idProductAttribute
						);
					} else if (event.reason.linkAction === 'add-to-cart') {
						if(event.reason.cart.products.length > 0) {
							const products = event.reason.cart.products;
							products.forEach(function(data, index) {
								if(parseInt(products[index].id_product) === event.reason.idProduct) {
									gtag('event', 'add_to_cart', {
										"items": [
											{
												"id": products[index].id_product,
												"name": products[index].name,
												"brand": products[index].manufacturer_name,
												"category": products[index].category,
												"variant": products[index].id_product_attribute,
												"quantity": products[index].quantity,
												"price": products[index].price,
											}
										],
									});
								}
							});
						}
					}
				}
			});
		}
	});

	$("body").on("click", ".js-product-miniature", function () {

		var elm = $(this).find('.ga-listing');

		gtag('event', 'select_content', {
			"content_type": "product",
			"items": [
				{
					"id": elm.attr('data-product-id'),
					"name": elm.attr('data-product-name'),
					"brand": elm.attr('data-product-brand'),
					"cat": elm.attr('data-product-cat'),
					"price": elm.attr('data-product-price'),
				}
			]
		});

	});

	function bmEventRemoveProduct(id, id_attribute) {
		$.ajax({
			type: 'POST',
			cache: false,
			dataType: 'json',
			url: bm_ajax_controller,
			data: {
				ajax: 1,
				action: 'GaRemoveProduct',
				id_product: id,
				id_attribute: id_attribute,
			},
			success: function (response) {
				if (response.success) {
					gtag('event', 'remove_from_cart', {
						"items": [
							{
								"id": response.data.id,
								"name": response.data.name,
								"brand": response.data.brand,
								"variant": response.data.variant,
								"price": response.data.price,
							}
						],
					});
				}
			},
			error: function (response) {
				console.log(response);
			}
		});
	}

</script>
{/literal}

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
<span class="bm-payment__elm" data-open-payment="card"></span>
<section>
	{if !empty($bm_short_description)}
		{if !empty($bm_description_url)}
			<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" class="bm-small-info" target="_blank" rel="noopener noreferrer">
				{$bm_short_description|escape:'htmlall':'UTF-8'}
			</a>
		{else}
			<span class="bm-small-info">
				{$bm_short_description|escape:'htmlall':'UTF-8'}
			</span>
		{/if}
	{else}
		<span class="bm-small-info">
			{l s='Enter your card details below.' mod='bluepayment'}
		</span>
	{/if}
	{if !empty($bm_description)}
		<div class="bm-payment-description" style="margin-top: 10px;">
			{$bm_description nofilter}
		</div>
	{/if}
</section>
<div class="card-widget-content">
	<div class="bluepayment-loader"></div>
	<div class="bluepayment-loader-bg"></div>

	<div id="card-widget-iframe-container">
		<iframe id="card-widget-iframe" title="{l s='Card payment widget' mod='bluepayment'}" frameborder="0" scrolling="no" style="width:100%; border:0;"></iframe>
	</div>

	<button id="card-widget-pay-button" type="button" class="btn btn-primary" disabled="disabled">
		{l s='Pay' mod='bluepayment'}
	</button>

	<span id="card-widget-url" style="display:none;"
	      data-charge-address="{$cardWidget_moduleLinkCharge|escape:'html':'UTF-8'}"
	      data-service-id="{$cardWidget_serviceId|escape:'html':'UTF-8'}"
	      data-amount="{$cardWidget_amount|escape:'html':'UTF-8'}"
	      data-currency="{$cardWidget_currency|escape:'html':'UTF-8'}"
	      data-language="{$cardWidget_language|escape:'html':'UTF-8'}">
	</span>

	<div id="responseCardWidgetMessages" class="help-block"></div>
</div>

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
 * @copyright      Copyright (c) 2015-2025
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
<span class="bm-payment__elm"></span>

{if $gpayRedirect}
	{if !empty($bm_short_description)}
		{if !empty($bm_description_url)}
			<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">
				<p>{$bm_short_description|escape:'htmlall':'UTF-8'}</p>
			</a>
		{else}
			<p>{$bm_short_description|escape:'htmlall':'UTF-8'}</p>
		{/if}
	{else}
		<p>{l s='You will be redirected to the website of our partner Autopay, where you can choose your fast and secure payment method.' mod='bluepayment'}</p>
	{/if}
	{if !empty($bm_description)}
		<div class="bm-payment-description" style="margin-top: 10px;">
			{$bm_description nofilter}
		</div>
	{/if}
{else}
	<section>
		{if !empty($bm_short_description)}
			{if !empty($bm_description_url)}
				<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" class="bm-small-info" target="_blank" rel="noopener noreferrer" style="display: block; margin-bottom: 15px;">
					{$bm_short_description|escape:'htmlall':'UTF-8'}
				</a>
			{else}
				<span class="bm-small-info" style="display: block; margin-bottom: 15px;">
					{$bm_short_description|escape:'htmlall':'UTF-8'}
				</span>
			{/if}
		{/if}
		{if !empty($bm_description)}
			<div class="bm-payment-description" style="margin-bottom: 15px;">
				{$bm_description nofilter}
			</div>
		{/if}
		<div style="padding-bottom: 25px">
			<div class="bluepayment-loader"></div>
			<div class="bluepayment-loader-bg"></div>
			<div id="gpay-button"></div>
			<span id="gpay-url" style="display:none;"
			      data-merchant-info-address="{$wallet_merchantInfo}"
			      data-charge-address="{$gpay_moduleLinkCharge}"></span>
			<div id="bm-termofuse" class="help-block js-g-pay-terms-of-use" style="display:none; color: red;">
                {l s='Please accept the [1]Transaction Regulations[/1]' tags=['<strong>'] mod='bluepayment'}
			</div>
			<div id="responseGPayMessages" class="help-block"></div>
		</div>
	</section>
{/if}

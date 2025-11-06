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
			{l s='You will be redirected to our partner Autopay website where you will enter your card details.' mod='bluepayment'}
		</span>
	{/if}
	{if !empty($bm_description)}
		<div class="bm-payment-description" style="margin-top: 10px;">
			{$bm_description nofilter}
		</div>
	{/if}
</section>
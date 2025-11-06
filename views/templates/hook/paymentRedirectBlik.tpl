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
<span class="bm-payment__elm" data-open-payment="blik" data-payment-redirect="false"></span>

<section>
	{if !empty($bm_short_description)}
		{if !empty($bm_description_url)}
			<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">
				<p>{$bm_short_description|escape:'htmlall':'UTF-8'}</p>
			</a>
		{else}
			<p>{$bm_short_description|escape:'htmlall':'UTF-8'}</p>
		{/if}
	{else}
		<p>{l s='You will be redirected to a page where you enter your BLIK code. You generate the BLIK code in your banking app.' mod='bluepayment'}</p>
	{/if}
	{if !empty($bm_description)}
		<div class="bm-payment-description" style="margin-top: 10px;">
			{$bm_description nofilter}
		</div>
	{/if}
</section>

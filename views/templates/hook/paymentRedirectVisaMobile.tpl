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
</span>
<section>
	<span class="bm-small-info">
		{if !empty($bm_short_description)}
			{if !empty($bm_description_url)}
				<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">
					{$bm_short_description|escape:'htmlall':'UTF-8'}
				</a>
			{else}
				{$bm_short_description|escape:'htmlall':'UTF-8'}
			{/if}
		{else}
			{l s='Enter your phone number and confirm the payment in the application.' mod='bluepayment'}
		{/if}
	</span>
</section>




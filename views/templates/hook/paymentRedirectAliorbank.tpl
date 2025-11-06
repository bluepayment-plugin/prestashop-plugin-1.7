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
<span class="bm-payment__elm bm-payment__promo" data-open-payment="aliorbank">
	<span class="bm-promo-desc">
		{if !empty($bm_short_description)}
			{if !empty($bm_description_url)}
				<a href="{$bm_description_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener noreferrer">
					{$bm_short_description|escape:'htmlall':'UTF-8'}
				</a>
			{else}
				{$bm_short_description|escape:'htmlall':'UTF-8'}
			{/if}
		{else}
			{l s='Spread the payment into convenient installments and buy without any problems.' mod='bluepayment'}
			<a target="_blank" href="https://kalkulator.raty.aliorbank.pl/init?supervisor=B776&promotionList=B">
				{l s='Find out more.' mod='bluepayment'}
			</a>
			<br />
		{/if}
	</span>
</span>

{if !empty($bm_description)}
	<section>
		{$bm_description nofilter}
	</section>
{/if}

<div class="modal bm-fade" id="aliorbank-desc" tabindex="-1" aria-hidden="true">
	<div class="bm-modal__dialog">
		<div class="bm-modal__content">
			<div class="bm-modal__header">
				<h5 class="bm-modal__title">
					{l s='Information about the credit intermediary and credit costs' mod='bluepayment'}
				</h5>
				<button type="button" class="bm-modal__close" data-dismiss="modal"
				        aria-label="{l s='Close' mod='bluepayment'}">
					<img src="{$bm_dir}views/img/close.svg" width="20"
					     alt="{l s='Close' mod='bluepayment'}"/>
				</button>
			</div>

			<div class="bm-modal__body">
				<h3>{l s='Credit intermediary' mod='bluepayment'}</h3>
				<p>
					{l s='Autopay S.A. as a credit intermediary cooperates with Alior Bank S.A. Scope of empowerment: presenting customers with a credit offer and redirecting them to the Alior Bank website S.A., including to the credit application.' mod='bluepayment'}
				</p>
				<h3>{l s='Information about the cost of credit' mod='bluepayment'}</h3>
				<p>
                    {l s='Credit offer - 0% loan for one month: The Annual Percentage Rate of Interest (APR) is 0%, net loan amount (excluding borrowed costs) PLN 1000, total amount to be paid PLN 1000, fixed interest rate 0%, total loan cost PLN 0 (including: commission PLN 0 interest PLN 0), 10 monthly equal installments of PLN 100. The calculation was made as of 29/03/2022 on a representative example. representative example.' mod='bluepayment'}
				</p>
			</div>

		</div>
	</div>
</div>

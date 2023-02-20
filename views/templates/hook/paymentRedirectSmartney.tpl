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
<span class="bm-payment__elm bm-payment__promo" data-open-payment="smartney">
	<span class="bm-promo-desc">
		{l s='Buy now and pay within 30 days.' mod='bluepayment'}
		<a target="_blank" href="https://pomoc.bluemedia.pl/platnosci-online-w-e-commerce/pay-smartney">
			{l s='Learn more' mod='bluepayment'}
		</a>
	</span>
</span>
<section>
	<p>
        {l s='You will be redirected to Smartney partner website. Once you have submitted your application and successfully verified, Smartney will pay for your purchases for you.' mod='bluepayment'}
	</p>
</section>


<div class="modal bm-fade" id="smartney-desc" tabindex="-1" aria-hidden="true">
	<div class="bm-modal__dialog">
		<div class="bm-modal__content">
			<div class="bm-modal__header">
				<h5 class="bm-modal__title">
					{l s='Information about the credit intermediary and RRSO' mod='bluepayment'}
				</h5>
				<button type="button" class="bm-modal__close" data-dismiss="modal"
				        aria-label="{l s='Close' mod='bluepayment'}">
					<img src="{$bm_dir}views/img/close.svg" width="20"
					     alt="{l s='Close' mod='bluepayment'}"/>
				</button>
			</div>

			<div class="bm-modal__body">
				<h3>{l s='Credit intermediary' mod='bluepayment'}</h3>
				<p>{l s='Blue Media S.A. as a credit intermediary works with [name of OTP provider]. Scope of empowerment: presentation of credit offer to customers and redirection to [name of OTP provider], including to the credit application.' mod='bluepayment'}</p>
				<h3>{l s='Actual Annual Percentage Rate of Interest (APR).' mod='bluepayment'}</h3>
				<p>
                    {l s='No-cost option: The Annual Percentage Rate of Interest (APR) is 0.00%.' mod='bluepayment'}
					{l s='Paid Option: The Annual Percentage Rate of Interest (APR) is 74.70%.' mod='bluepayment'}
				</p>
			</div>

		</div>
	</div>
</div>




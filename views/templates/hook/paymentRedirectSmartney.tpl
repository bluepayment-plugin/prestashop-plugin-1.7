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
	<span class="bm-promo-label">
		{l s='RECOMMENDED' mod='bluepayment'}
	</span>
	<span class="bm-promo-desc" style="display:none;">
{*		Kup teraz i zapłać w ciągu 30 dni*}
{*		<span class="bm-promo-desc--info">*}
{*			Informacja o pośredniku kredytowym i RRSO.*}
{*			<a href="#" data-toggle="modal" data-name="smartney" data-target="#smartney-desc">Pokaż</a>*}
{*		</span>*}
	</span>
</span>
<section>
    <h2 class="bm-payment__subtitle">
        {l s='Buy now and pay within 30 days.' mod='bluepayment'}
    </h2>
	<p>
        {l s='You will be redirected to Smartney partner website. Once you have submitted your application and successfully verified, Smartney will pay for your purchases for you.' mod='bluepayment'}
	</p>
</section>


<div class="modal bm-fade" id="smartney-desc" tabindex="-1" aria-hidden="true">
	<div class="bm-modal__dialog">
		<div class="bm-modal__content">
			<div class="bm-modal__header">
				<h5 class="bm-modal__title">
					Informacja o pośredniku kredytowym i RRSO
				</h5>
				<button type="button" class="bm-modal__close" data-dismiss="modal"
				        aria-label="{l s='Close' mod='bluepayment'}">
					<img src="{$module_dir}views/img/close.svg" width="20"
					     alt="{l s='Close' mod='bluepayment'}"/>
				</button>
			</div>

			<div class="bm-modal__body">
				<h3>Pośrednik kredytowy</h3>
				<p>Blue Media S.A. jako pośrednik kredytowy współpracuje z [nazwa dostawcy OTP]. Zakres umocowania:
					prezentowanie klientom oferty kredytowej oraz przekierowanie do serwisu internetowego [nazwa
					dostawcy OTP], w tym do wniosku kredytowego.</p>
				<h3>Rzeczywista Roczna Stopa Oprocentowania (RRSO)</h3>
				<p>Wariant bez kosztów: Rzeczywista Roczna Stopa Oprocentowania (RRSO) wynosi 0,00 %.
					Wariant Płatny: Rzeczywista Roczna Stopa Oprocentowania (RRSO) wynosi 74,70%.
				</p>
			</div>

		</div>
	</div>
</div>




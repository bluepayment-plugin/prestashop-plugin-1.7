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
<span class="bm-payment__elm bm-payment__promo" data-open-payment="aliorbank">
	<span class="bm-promo-label">
		{l s='NEW' mod='bluepayment'}
	</span>
	<span class="bm-promo-desc" style="display:none;">
{*		Już od 10,42 x 24 raty 0% <a href="#">Sprawdź</a><br/>*}
{*		<span class="bm-promo-desc--info">*}
{*			Informacja o pośredniku kredytowym i kosztach kredytu.*}
{*			<a href="#" data-toggle="modal" data-name="aliorbank" data-target="#aliorbank-desc">Pokaż</a>*}
{*		</span>*}
	</span>
</span>
<section>
    <h2 class="bm-payment__subtitle">
        {l s='0% or even 48 installments' mod='bluepayment'}
    </h2>
	<p>
        {l s='We will redirect you to the bank website. After your application and positive verification, the bank will send you a loan agreement via email. You can accept it online. Average time of the whole transaction - 15 minutes.' mod='bluepayment'}
	</p>
</section>

<div class="modal bm-fade" id="aliorbank-desc" tabindex="-1" aria-hidden="true">
	<div class="bm-modal__dialog">
		<div class="bm-modal__content">
			<div class="bm-modal__header">
				<h5 class="bm-modal__title">
					Informacja o pośredniku kredytowym i kosztach kredytu
				</h5>
				<button type="button" class="bm-modal__close" data-dismiss="modal"
				        aria-label="{l s='Close' mod='bluepayment'}">
					<img src="{$module_dir}views/img/close.svg" width="20"
					     alt="{l s='Close' mod='bluepayment'}"/>
				</button>
			</div>

			<div class="bm-modal__body">
				<h3>Pośrednik kredytowy</h3>
				<p>Blue Media S.A. jako pośrednik kredytowy współpracuje z Alior Bank S.A. Zakres umocowania:
					prezentowanie klientom oferty kredytowej oraz przekierowanie do serwisu internetowegoAlior Bank
					S.A., w tym do wniosku kredytowego.</p>
				<h3>Informacja o kosztach kredytu</h3>
				<p>Oferta kredytowa – pożyczka 0% za miesiąc: Rzeczywista Roczna Stopa Oprocentowania (RRSO) wynosi 0%,
					kwota pożyczki netto (bez kredytowanych kosztów) 1000 zł, całkowita kwota do zapłaty 1000 zł,
					oprocentowanie stałe 0%, całkowity koszt pożyczki 0 zł (w tym: prowizja 0 zł odsetki 0 zł), 10
					miesięcznych równych rat w wysokości 100 zł. Kalkulacja została dokonana na dzień 29.03.2022 r. na
					reprezentatywnym przykładzie.
				</p>
			</div>

		</div>
	</div>
</div>

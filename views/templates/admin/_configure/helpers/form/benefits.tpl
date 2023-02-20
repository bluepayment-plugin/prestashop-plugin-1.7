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
<div class="panel">

	<style>
		.bm-info__item::before {
            background-image: url('/modules/bluepayment/views/img/check.png');
		}
	</style>

	<div class="bm-info">

		<img class="bm-info__img" src="{$src_img|escape:'html':'UTF-8'}/blue-media.svg" alt="Blue Media">
		<ul class="bm-info__list">
			<li class="bm-info__item">
                {l s='Commission only 1.19%' mod='bluepayment'}
			</li>
{*			<li class="bm-info__item">*}
{*                {l s='SEO audit at a promotional price.' mod='bluepayment'}*}
{*				<a href="#">{l s='Find out more' mod='bluepayment'}</a>*}
{*			</li>*}
			<li class="bm-info__item">
                {l s='Prepare shop regulations 10% cheaper.' mod='bluepayment'}
				<a target="_blank" href="https://developers.bluemedia.pl/legal-geek?mtm_campaign=presta_shop_legalgeek&mtm_source=presta_shop_backoffice&mtm_medium=cta">{l s='Find out more' mod='bluepayment'}</a>
			</li>
		</ul>

	</div>


</div>

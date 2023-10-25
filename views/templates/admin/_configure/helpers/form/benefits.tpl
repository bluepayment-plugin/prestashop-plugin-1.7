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
 * @copyright      Copyright (c) 2015-2023
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
<div class="panel">

	<style>
		.bm-info__item::before {
            background-image: url('/modules/bluepayment/views/img/check.png');
		}
	</style>

	<div class="bm-info">

		<img class="bm-info__img" src="{$src_img|escape:'html':'UTF-8'}/blue-media.svg" alt="Autopay">
		<ul class="bm-info__list">
			<li class="bm-info__item">
                {l s='Commission only [1] 1.19% + 0,25 zł' tags=['<br />'] mod='bluepayment'}
			</li>
{*			<li class="bm-info__item">*}
{*                {l s='SEO audit at a promotional price.' mod='bluepayment'}*}
{*				<a href="#">{l s='Find out more' mod='bluepayment'}</a>*}
{*			</li>*}
			<li class="bm-info__item">
                {l s='Prepare shop regulations 10% cheaper.' mod='bluepayment'}
				<a target="_blank" href="https://marketplace.autopay.pl/">{l s='Find out more' mod='bluepayment'}</a>
			</li>
		</ul>

	</div>


</div>

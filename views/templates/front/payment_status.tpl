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
 * @copyright      Copyright (c) 2015-2026
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
{extends file=$layout}

{block name='content'}
	<section id="main" class="bm-payment-status" style="padding: 20px">
		<div class="box">
			<a href="https://bluepayment.pl" target="_blank">
				<img src="{$bm_dir|escape:'html':'UTF-8'}views/img/bluepayment.svg" class="payment-brand" alt="Bluemedia" />
			</a>
			<h1 class="bm-payment-status__title">
                {l s='Payment status' mod='bluepayment'}
			</h1>
			<p class="warning bm-payment-status__text">
                {l s='Transaction status unknown.' mod='bluepayment'}
			</p>
            {if isset($error)}
				<p class="bm-payment-status__text">
					<strong>
                        {$error|escape:'html':'UTF-8'}
					</strong>
				</p>
            {/if}
			<div class="payment-navigation cart_navigation">
				<a href="{$urls.base_url|escape:'html':'UTF-8'}" class="btn btn-primary bm-payment-status__btn">
                    {l s='Return to the shop' mod='bluepayment'}
				</a>
				<a class="btn btn-primary bm-payment-status__btn" href="{$urls.pages.history|escape:'html':'UTF-8'}">
                    {l s='View order history' mod='bluepayment'}
				</a>
			</div>
		</div>
	</section>
{/block}

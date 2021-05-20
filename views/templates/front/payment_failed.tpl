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
 * @copyright      Copyright (c) 2015-2020
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
{extends file=$layout}

{block name='content'}
    <section id="main" style="padding: 20px">
        <div class="box">
            <h2>
                <a href="http://bluepayment.pl" target="_blank">
                    <img src="{$module_dir}views/img/logo.jpg"
                         alt="Bluemedia" />
                </a>
                {l s='Payment failed' mod='bluepayment'}
            </h2>
            <p class="warning">
                {l s='There was a problem with your orders. Please contact us for more questions' mod='bluepayment'}
            </p>
            {if isset($errorReason)}
                <p>
                    <strong>
                        {$errorReason}
                    </strong>
                </p>
            {/if}
            <p class="cart_navigation">
                <a href="{$urls.base_url}" class="btn btn-primary">
                    {l s='Return to the shop' mod='bluepayment'}
                </a>
                <a class="btn btn-primary" href="{$urls.pages.history}">
                    {l s='View order history' mod='bluepayment'}
                </a>
            </p>
        </div>
    </section>
{/block}

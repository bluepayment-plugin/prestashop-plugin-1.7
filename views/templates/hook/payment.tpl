{*
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2016
 * @license        https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
 *}
<section>
    <p class="logo_background">
        <span>
            &nbsp;{$payment_name}<br/>
            ({$payment_name_extra})
        </span>
        {if $selectPayWay}
    <form id="bluepayment-gateway" method="GET">
        <div id="blue_payway">
            <h1 class="page-heading step-num">{l s='Select bank' mod='bluepayment'}</h1>
            <div class="row">
                {foreach from=$gateways item=row name='gateways'}
                <div class="col-xs-6">

                    <label>
                        {*<span class="custom-radio pull-xs-left">*}
                        <input value="{$row->gateway_id}" name="bluepayment-gateway-gateway-id" type="radio" required="">
                        {*</span>*}

                        {if $showPayWayLogo}
                            <img style="width: 60px;" src="{$row->gateway_logo_url}" alt="{$row->gateway_name}">
                        {/if}
                        {$row->gateway_name}
                    </label>
                </div>
                {if $smarty.foreach.gateways.iteration is div by 2}
            </div><div class="row">
                {/if}
                {/foreach}
            </div>
        </div>
    </form>

    {/if}
    </p>
    {if $showBaner}
        <br/>
        <img src="{$module_dir}img/baner.png" style="width: 100%;"/>
    {/if}
</section>
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

<p class="payment_module test_module" {$selectPayWay}>
    {if $ps_version < '1.5'}
        <a href="{$module_link}" title="{$payment_name}" {if $selectPayWay} onclick="return selectBluePayment()"{/if}>
            <span>
                <img src="{$module_dir}logo.png" alt="{$payment_name}" />
                {$payment_name}
                ({$payment_name_extra})
            </span>
        </a>
            
    {else}
        <a class="logo_background" href="{$module_link}" title="{$payment_name}" {if $selectPayWay} onclick="return selectBluePayment()"{/if}>
            &nbsp;{$payment_name}
            <span>({$payment_name_extra})</span>
        </a>
        {if $showBaner}
            <img src="{$module_dir}img/baner.png" style="width: 100%;"/>
        {/if}
        {if $selectPayWay}
            <div id="blue_payway" style="display: none;">
                <h1 class="page-heading step-num">{l s='Select bank' mod='bluepayment'}</h1>
                <div class="row">
                    {foreach from=$gateways item=row}
                    <div class="col-xs-3">
                        <a href="{$module_link}?gateway_id={$row->gateway_id}" class="thumbnail">
                            {if $showPayWayLogo}<img src="{$row->gateway_logo_url}" alt="{$row->gateway_name}">{/if}
                            <center>{$row->gateway_name}</center>
                        </a>

                    </div>
                    {/foreach}
                </div>  
            </div>
        {/if}
    {/if}
    
    
</p>
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
<section>
    <span>
    {if isset($payment_name_extra)}
        {$payment_name_extra}
    {/if}
    </span>
    {if $showBaner}
        <img src="{$module_dir}views/img/baner.png" style="width: 100%;" />
    {/if}
    {if $selectPayWay}
        <form id="bluepayment-gateway" method="GET">
            <div class="bluepayment-agent-info">
                {l s='The payment order is submitted to your bank via Blue Media S.A. based in Sopot and will be processed in accordance with the terms and conditions specified by your bank.' mod='bluepayment'}
            </div>
            <div id="blue_payway">
                <div class="row">
                    {foreach from=$gateways item=row name='gateways'}
                    <div class="col-xs-6 blue-gateway">
                        <label>
                            <input type="radio" name="bluepayment-gateway-gateway-id" value="{$row->gateway_id}" required="required">
                            {if $showPayWayLogo}
                            <img src="{$row->gateway_logo_url}" alt="{$row->gateway_name}">
                            {/if}
                            <span class="payway-text">{$row->gateway_name}</span>
                        </label>
                    </div>
                    {if $smarty.foreach.gateways.iteration is div by 2}
                </div>
                <div class="row">
                    {/if}
                    {/foreach}
                </div>
            </div>
            <!-- wyswietlamy klauzle jezeli mamy przynajmniej jeden kanał PIS (a mamy go gdy regulationsGet zwroci min 1 val )-->
            <div class="bluepayment-agent-info-bottom ajax-psd2-clause" style="display:none; width:100%; min-height:145px;">
                <div class="text"></div>
            </div>

            <div class="bluepayment-agent-info-bottom ajax-psd2-clause-merchant" style="display:none; width:100%; min-height:145px;">
                <div class="text"></div>
            </div>
        </form>
    {/if}
    <script>
        var regulations_get_url = '{$regulations_get}';
        var start_payment_translation = '{$start_payment_translation}';
        var order_subject_to_payment_obligation_translation = '{$order_subject_to_payment_obligation_translation}';
    </script>
</section>

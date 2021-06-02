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
{if count($massage) > 0 }
    <div class="bootstrap">
        {foreach from=$massage item=row}
            <div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert">×</button>
                {$row}
            </div>
        {/foreach}
    </div>
{/if}
{if count($error) > 0 }
    <div class="bootstrap">
        {foreach from=$error item=row2}
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">×</button>
                {$row2}
            </div>
        {/foreach}
    </div>
{/if}
<div class="panel col-lg-12">
    <div class="panel-heading">
        {l s='Payment channels' mod='bluepayment'}
        <span class="badge">{count($gateways)}</span>
        <a href="{$link->getAdminLink('AdminBluepayment')|escape:'html':'UTF-8'}&amp;download_gateway" class="btn btn-primary pull-right">{l s='Update payment channels' mod='bluepayment'}</a>
    </div>
    <div class="table-responsive-row clearfix">
        <table class="table order">
            <thead>
            <tr class="nodrag nodrop">
                <th class="">
                    <span class="title_box">{l s='Gateway ID!' mod='bluepayment'}<span>
                </th>
                <th class="">
                    <span class="title_box">{l s='Bank name!' mod='bluepayment'}<span>
                </th>
                <th class="">
                    <span class="title_box">{l s='Status' mod='bluepayment'}<span>
                </th>
                <th class="">
                    <span class="title_box">{l s='Name' mod='bluepayment'}<span>
                </th>
                <th class="">
                    <span class="title_box">{l s='Logo' mod='bluepayment'}<span>
                </th>
                <th class="">
                </th>
            </thead>
            <tbody>
            {foreach from=$gateways item=gateway}
                <tr class="odd">
                    <td class="pointer fixed-width-xs text-center">
                        {$gateway->gateway_id}
                    </td>
                    <td class="pointer fixed-width-xs text-center">
                        {$gateway->bank_name}
                    </td>
                    <td class="pointer fixed-width-xs text-center">
                        {if $gateway->gateway_status == 1}
                            <span class="label color_field" style="background-color:#32CD32;color:#383838">
                                {l s='Active' mod='bluepayment'}
                            </span>
                        {else}
                            <span class="label color_field" style="background-color:#DC143C;color:white">
                                {l s='Inactive' mod='bluepayment'}
                            </span>
                        {/if}
                    </td>
                    <td class="pointer fixed-width-xs text-center">
                        {$gateway->gateway_name}
                    </td>
                    <td class="pointer fixed-width-xs text-center">
                        <img src="{$gateway->gateway_logo_url}">
                    </td>

                    <td class="text-right">
                        <div class="btn-group pull-right">
                            {if $gateway->gateway_status == 1}
                                <a href="{$link->getAdminLink('AdminBluepayment')|escape:'html':'UTF-8'}&amp;change_status&amp;gateway_id={$gateway->gateway_id}" class="btn btn-warning" title="Deaktywuj">
                                    {l s='Deactivate' mod='bluepayment'}
                                </a>
                            {else}
                                <a href="{$link->getAdminLink('AdminBluepayment')|escape:'html':'UTF-8'}&amp;change_status&amp;gateway_id={$gateway->gateway_id}" class="btn btn-success" title="Aktywuj">
                                    {l s='Activate' mod='bluepayment'}
                                </a>
                            {/if}
                        </div>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>

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
        Kanały płatności
        <span class="badge">{count($gateways)}</span>
        <a href="{$link->getAdminLink('AdminBluepayment')|escape:'html':'UTF-8'}&amp;download_gateway" class="btn btn-primary pull-right">Aktualizuj kanały płatności</a>
    </div>
    <div class="table-responsive-row clearfix">
        <table class="table order">
            <thead>
		<tr class="nodrag nodrop">
                <th class="">
                    <span class="title_box">Gateway ID<span>
                </th>
                <th class="">
                    <span class="title_box">Nazwa banku<span>
                </th>
                <th class="">
                    <span class="title_box">Status<span>
                </th>
                <th class="">
                    <span class="title_box">Nazwa<span>
                </th>
                <th class="">
                    <span class="title_box">Logo<span>
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
                                Aktywny
                            </span>
                        {else}
                            <span class="label color_field" style="background-color:#DC143C;color:white">
                                Nieaktywny
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
                                Deaktywuj
                            </a>
                        {else}
                            <a href="{$link->getAdminLink('AdminBluepayment')|escape:'html':'UTF-8'}&amp;change_status&amp;gateway_id={$gateway->gateway_id}" class="btn btn-success" title="Aktywuj">
                                Aktywuj
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
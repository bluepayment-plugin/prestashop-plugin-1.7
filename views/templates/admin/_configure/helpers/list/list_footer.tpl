{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
</table>
</div>
<div class="row">
	<div class="col-lg-6">
		{if $bulk_actions && $has_bulk_actions}
		<div class="btn-group bulk-actions dropup">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" {if $table}id="bulk_action_menu_{$table}"{/if}>
				{l s='Bulk actions' d='Admin.Global'} <span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li>
					<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id}Box[]', true);return false;">
						<i class="icon-check-sign"></i>&nbsp;{l s='Select all' d='Admin.Global'}
					</a>
				</li>
				<li>
					<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id}Box[]', false);return false;">
						<i class="icon-check-empty"></i>&nbsp;{l s='Unselect all' d='Admin.Global'}
					</a>
				</li>
				<li class="divider"></li>
				{foreach $bulk_actions as $key => $params}
					<li{if $params.text == 'divider'} class="divider"{/if}>
						{if $params.text != 'divider'}
						<a href="#" onclick="{if isset($params.confirm)}if (confirm('{$params.confirm}')){/if}sendBulkAction($(this).closest('form').get(0), 'submitBulk{$key}{$table}');">
							{if isset($params.icon)}<i class="{$params.icon}"></i>{/if}&nbsp;{$params.text}
						</a>
						{/if}
					</li>
				{/foreach}
			</ul>
		</div>
		{/if}
	</div>
</div>
{block name="footer"}
{foreach from=$toolbar_btn item=btn key=k}
	{if $k == 'back'}
		{assign 'back_button' $btn}
		{break}
	{/if}
{/foreach}
{if isset($back_button)}
<div class="panel-footer">
	<a id="desc-{$table}-{if isset($back_button.imgclass)}{$back_button.imgclass}{else}{$k}{/if}" class="btn btn-default{if isset($back_button.target) && $back_button.target} _blank{/if}"{if isset($back_button.href)} href="{$back_button.href|escape:'html':'UTF-8'}"{/if}{if isset($back_button.js) && $back_button.js} onclick="{$back_button.js}"{/if}>
		<i class="process-icon-back {if isset($back_button.class)}{$back_button.class}{/if}" ></i> <span {if isset($back_button.force_desc) && $back_button.force_desc == true } class="locked" {/if}>{$back_button.desc}</span>
	</a>
</div>
{/if}
{/block}
{if !$simple_header}
		<input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}" />
	</div>
{else}
	</div>
{/if}

{hook h='displayAdminListAfter'}
{if isset($name_controller)}
	{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
	{hook h=$hookName}
{elseif isset($smarty.get.controller)}
	{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
	{hook h=$hookName}
{/if}

{block name="endForm"}
</form>
{/block}

{block name="after"}{/block}

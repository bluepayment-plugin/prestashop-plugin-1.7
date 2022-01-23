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
<div class="bm-menu">
	<ul class="nav nav-pills">
        {$tabk = 0}

        {foreach $fields as $fkey => $fvalue}

            {if $fkey === 0}
				<li class="nav-item">
					<a href="tab_rule_{$tabk}" class="nav-link tab " id="tab_rule_link_{$tabk}"
					   href="javascript:displaythemeeditorTab('{$tabk}');">
                        {$fvalue.form.section.title}
					</a>
				</li>
            {/if}

            {if $fkey === 2}
				<li class="nav-item">
					<a href="tab_rule_{$tabk}" class="nav-link tab " id="tab_rule_link_{$tabk}"
					   href="javascript:displaythemeeditorTab('{$tabk}');">
                        {$fvalue.form.section.title}
					</a>
				</li>
            {/if}

            {$tabk = $tabk+1}
        {/foreach}
	</ul>
</div>

<div class="bm-configure">

	<div class="col-md-9">

        {if isset($fields.title)}
			<h3>{$fields.title}</h3>
        {/if}

        {block name="defaultForm"}

            {if isset($identifier_bk) && $identifier_bk == $identifier}
                {capture name='identifier_count'}{counter name='identifier_count'}{/capture}
            {/if}

            {assign var='identifier_bk' value=$identifier scope='parent'}
            {if isset($table_bk) && $table_bk == $table}
                {capture name='table_count'}{counter name='table_count'}{/capture}
            {/if}

            {assign var='table_bk' value=$table scope='parent'}
			<form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'html':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table}_form{/if}{if isset($smarty.capture.table_count) && $smarty.capture.table_count}_{$smarty.capture.table_count|intval}{/if}{/if}"
			      class="defaultForm form-horizontal{if isset($name_controller) && $name_controller} {$name_controller}{/if}"{if isset($current) && $current} action="{$current|escape:'html':'UTF-8'}{if isset($token) && $token}&amp;token={$token|escape:'html':'UTF-8'}{/if}"{/if}
			      method="post" enctype="multipart/form-data"{if isset($style)} style="{$style}"{/if} novalidate>
                {if $form_id}
					<input type="hidden" name="{$identifier}"
					       id="{$identifier}{if isset($smarty.capture.identifier_count) && $smarty.capture.identifier_count}_{$smarty.capture.identifier_count|intval}{/if}"
					       value="{$form_id}"/>
                {/if}
                {if !empty($submit_action)}
					<input type="hidden" name="{$submit_action}" value="1"/>
                {/if}
                {$tabkey = 0}


                {foreach $fields as $f => $fieldset}
                    {foreach $fieldset.form.section as $fieldset2}


                        {if $f == 0}
							<div id="tab_rule_{$tabkey}" class="{$submit_action} tab_rule_tab ">
                            {include file="module:bluepayment/views/templates/admin/_configure/helpers/form/benefits.tpl"}

                        {elseif $f == 2}
							<div id="tab_rule_{$tabkey}" class="{$submit_action} tab_rule_tab ">
                        {/if}


                        {block name="fieldset"}
                            {capture name='fieldset_name'}{counter name='fieldset_name'}{/capture}
							<div class="panel"
							     id="fieldset_{$f}{if isset($smarty.capture.identifier_count) && $smarty.capture.identifier_count}_{$smarty.capture.identifier_count|intval}{/if}{if $smarty.capture.fieldset_name > 1}_{($smarty.capture.fieldset_name - 1)|intval}{/if}">
                                {foreach $fieldset.form as $key => $field}

                                    {if $key == 'legend'}
                                        {block name="legend"}
											<div class="panel-heading">
                                                {if isset($field.image) && isset($field.title)}<img src="{$field.image}"
												                                                    alt="{$field.title|escape:'html':'UTF-8'}" />{/if}
                                                {if isset($field.icon)}<i class="{$field.icon}"></i>{/if}
                                                {$field.title}
											</div>
                                        {/block}
                                    {elseif $key == 'description' && $field}
										<!-- <div class="alert alert-info">{$field}</div> -->
                                    {elseif $key == 'input'}

                                        {foreach $field as $input}
                                            {include file="module:bluepayment/views/templates/admin/_configure/helpers/form/configure_fields.tpl" _input=$input}
                                        {/foreach}

                                    {elseif $key == 'form_group'}

                                        {foreach $fieldset.form.form_group.fields as $key2 => $fields_group_input}
                                            {foreach $fields_group_input as $kkk => $fields_group_form}
                                                {foreach $fields_group_form as $form_key => $form_subgroup_input}

                                                    {if $form_key === 'legend'}
														<div class="section-heading">
                                                            {$form_subgroup_input.title}
														</div>
                                                    {elseif $form_key === 'input'}

                                                        {foreach $form_subgroup_input as $form_subgroup_field}
                                                            {include file="module:bluepayment/views/templates/admin/_configure/helpers/form/configure_fields.tpl" _input=$form_subgroup_field}
                                                        {/foreach}

                                                    {/if}

                                                {/foreach}
                                            {/foreach}
                                        {/foreach}



                                    {/if}



                                {/foreach}

                                {block name="footer"}
                                    {capture name='form_submit_btn'}{counter name='form_submit_btn'}{/capture}
                                    {if isset($fieldset['form']['submit']) || isset($fieldset['form']['buttons'])}
										<div class="panel-footer">

                                            {if isset($fieldset['form']['submit']) && !empty($fieldset['form']['submit'])}
												<button type="submit" value="1"
												        id="{if isset($fieldset['form']['submit']['id'])}{$fieldset['form']['submit']['id']}{else}{$table}_form_submit_btn{/if}{if $smarty.capture.form_submit_btn > 1}_{($smarty.capture.form_submit_btn - 1)|intval}{/if}"
												        name="{if isset($fieldset['form']['submit']['name'])}{$fieldset['form']['submit']['name']}{else}{$submit_action}{/if}{if isset($fieldset['form']['submit']['stay']) && $fieldset['form']['submit']['stay']}AndStay{/if}"
												        class="{if isset($fieldset['form']['submit']['class'])}{$fieldset['form']['submit']['class']}{else}btn btn-primary pull-right{/if}">
                                                    {$fieldset['form']['submit']['title']}
												</button>
                                            {/if}

                                            {if isset($fieldset['form']['buttons'])}
                                                {foreach from=$fieldset['form']['buttons'] item=btn key=k}
                                                    {if isset($btn.href) && trim($btn.href) != ''}
														<a href="{$btn.href}"
                                                           {if isset($btn['id'])}id="{$btn['id']}"{/if}
														   class="btn btn-primary{if isset($btn['class'])} {$btn['class']}{/if}" {if isset($btn.js) && $btn.js} onclick="{$btn.js}"{/if}>{if isset($btn['icon'])}
																<i class="{$btn['icon']}"></i>
                                                            {/if}{$btn.title}</a>
                                                    {else}
														<button type="button"
                                                                {if isset($btn['id'])}id="{$btn['id']}"{/if}
														        class="btn btn-primary{if isset($btn['class'])} {$btn['class']}{/if}"
														        name="{if isset($btn['name'])}{$btn['name']}{else}submitOptions{$table}{/if}"{if isset($btn.js) && $btn.js} onclick="{$btn.js}"{/if}>{if isset($btn['icon'])}
																<i class="{$btn['icon']}"></i>
                                                            {/if}{$btn.title}
														</button>
                                                    {/if}
                                                {/foreach}
                                            {/if}

										</div>
                                    {/if}
                                {/block}
							</div>
                        {/block}
                        {block name="other_fieldsets"}{/block}

                        {if $f == 1}
							</div>
                        {elseif $f == 2}
                            {hook h='adminPayments'}
                        {elseif $f == 3}
							</div>
                        {/if}

                    {/foreach}

                    {$tabkey = $tabkey+1}
                {/foreach}

			</form>
        {/block}
        {block name="after"}{/block}


	</div>


	<script type="text/javascript">
		$('.tab_rule_tab').hide();

		$('#tab_rule_link_0').addClass('active');
		$('#tab_rule_0').show();


		$('.bm-menu li').on('click', function (e) {

			e.preventDefault();

			var target = $(e.target).attr("href");

			$('.bm-menu li a').removeClass('active');
			$(this).find('a').addClass('active');

			$('.tab_rule_tab').hide();
			$('#' + target).show();
		});

	</script>

    {if $firstCall}
		<script type="text/javascript">
			var module_dir = '{$smarty.const._MODULE_DIR_}';
			var id_language = {$defaultFormLanguage|intval};
			var languages = new Array();

            {foreach $languages as $k => $language}
			languages[{$k}] = {
				id_lang: {$language.id_lang},
				iso_code: '{$language.iso_code}',
				name: '{$language.name}',
				is_default: '{$language.is_default}'
			};
            {/foreach}

			allowEmployeeFormLang = {$allowEmployeeFormLang|intval};
			displayFlags(languages, id_language, allowEmployeeFormLang);


			function initChangesTable() {
				$('.blue_gateway_channels').find('th, td').filter(':nth-child(2)').append(function () {
					return $(this).next().html();
				}).next().remove();
			}

			$(document).ready(function () {

				const payTest = $("input[name=BLUEPAYMENT_TEST_ENV]:checked").val();
				const showPayWay = $("input[name=BLUEPAYMENT_SHOW_PAYWAY]:checked").val();

				$("input[name=BLUEPAYMENT_SHOW_PAYWAY]").click(function (e) {
					checkShowPayway($(this).val());
				})

				$("input[name=BLUEPAYMENT_TEST_ENV]").click(function (e) {
					checkPayTest($(this).val());
				})

				function checkShowPayway(state) {
					if (state == 1) {
						$('.bluepayment_payment_group_name').show();
						$('.bluepayment_payment_name').hide();
						$('.paymentList').show();

					} else {
						$('.bluepayment_payment_group_name').hide();
						$('.bluepayment_payment_name').show();
						$('.paymentList').hide();
					}
				}

				function checkPayTest(state) {
					if (state == 1) {
						$('.bm-info--small').show();

					} else {
						$('.bm-info--small').hide();
					}
				}

				checkPayTest(payTest);
				checkShowPayway(showPayWay);

				initChangesTable();

                {if isset($use_textarea_autosize)}
				$(".textarea-autosize").autosize();
                {/if}
			});

			state_token = '{getAdminToken tab='AdminStates'}';
            {block name="script"}{/block}
		</script>


		<script type="text/javascript">
			let bm_ajax = "{$ajax_controller}"
			let bm_token = "{$ajax_token}";
			let bm_token2 = "{$ajax_payments_token}";

			let success_msg = "{l s='Configuration saved successfully' mod='bluepayment'}"
			let error_msg = "{l s='Error, configuration not saved' mod='bluepayment'}"

			$(document).ready(function () {
				$('form').on('submit', function (e) {
					e.preventDefault();

					var data = $(this).serialize() + '&ajax=true&action=SaveConfiguration&token=' + bm_token;

					$.ajax({
						type: 'POST',
						cache: false,
						dataType: 'json',
						url: bm_ajax,
						data: data,
						success: function (data) {
							if(data.success) {

								reloadPaymentGateway();
								showSuccessMessage(success_msg);

							} else {
								showErrorMessage(error_msg);
							}
						},
						error: function (data) {
							showErrorMessage(error_msg);
						}
					});
				});
			});

			var data2 = 'ajax=true&action=ReloadPaymentsGateway';

			function reloadPaymentGateway() {
				$.ajax({
					url: bm_ajax,
					type: 'GET',
					cache: false,
					dataType: 'html',
					data: data2,
					success: function (data2) {
						$('.paymentList').html($(data2).find('.paymentList').html());
						initChangesTable();
					},
					error: function (data2) {
						console.log(data2);
					}
				});
			}
		</script>
    {/if}

</div>

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
<style>
    .bm-configure {
        display: flex;
        flex-direction: column;
        margin-left: auto;
        margin-right: auto;
        align-items: center;
        width: 100%;
        margin-top: 80px;
        font-size: 14px;
        color: #000000;
    }

    .bm-configure p {
        font-size: 13px;
    }

    .bm-configure .control-label {
        color: #000000 !important;
    }

    .bm-configure .panel-footer {
        height: 60px !important;
    }

    .translatable-field {
        width: 100%;
    }

    .help-text {
        padding-top: 10px;
        color: #7e7e7e;
        font-size: 13px;
    }

    .bm-menu {
        width: 100%;
        background: #fff;
        margin-top: -15px;
        position: fixed;
        font-size: .875rem;
        z-index: 10;
    }

    .bm-menu .nav-pills {
        border-top: 1px solid #dfdfdf;
    }

    .bm-menu .nav-pills .nav-link.active {
        border-bottom: 3px solid #25b9d7;
        color: #363a41 !important;
        background-color: #f4f9fb !important;
    }

    .bm-menu .nav-link {
        display: block;
        padding: .9375rem 1.25rem !important;
        color: #6c868e !important;
        position: relative;
        background-color: #ffffff !important;
    }

    .bm-menu .nav {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        padding-left: 0;
        margin-bottom: 0;
        list-style: none;
    }

    .bm-list {
        margin-left: 2px;
        padding: 0;
        padding-left: 10px;
    }

    .bm-list li {
        padding-bottom: 6px;
    }

    .bm-select {
        max-width: 280px;
    }

    .btn-primary {
        padding: .375rem .838rem !important;
        font-size: .875rem !important;
        text-transform: none !important;
        border-radius: 1px !important;
        font-weight: 600 !important;
        background: #25b9d7 !important;
        line-height: 1.5 !important;
    }

    .panel-footer {
        background-color: #ffffff;
    }

    .panel {
        padding-bottom: 10px;
    }

    .section-heading {
        color: #000000;
        font-weight: 600 !important;
        border-bottom: 1px solid #56B6D3;
        font-size: 14px;
        line-height: 1.5 !important;
        padding: 8px 0;
        margin-bottom: 30px;
        margin-top: 30px;
    }

    .form-group {
        margin-left: auto;
        margin-right: auto;
        display: flex;
        flex-wrap: wrap;
        align-content: center;
    }

    .bootstrap .form-horizontal .control-label {
        text-align: left !important;
    }

    @media only screen and (min-width: 992px) {
        .form-group {
            justify-content: center;
        }

        .bootstrap .form-horizontal .control-label {
            text-align: right !important;
        }
    }


    .panel-footer {
        margin: 30px -20px -20px !important;
    }

    .panel-heading {
        margin: -20px -16px 30px !important;
    }

    .bootstrap .table {
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .bm-info {
        display: flex;
        align-items: center;
        /*flex-wrap: wrap;*/
    }

    .bm-info__list {
        display: flex;
        align-content: center;
        list-style-type: none;
        padding: 0;
        width: 100%;
        /*flex-wrap: wrap;*/
    }

    .bm-info__img {
        padding-right: 30px;
        height: 39px;
    }

    .bm-info__item {
        padding: 15px 0 15px 28px;
        /*padding-right: 0px;*/
        font-size: 14px;
        width: 33.3%;
        position: relative;
    }

    .bm-info__item::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0px;
        width: 20px;
        height: 20px;
        background-image: url('/modules/bluepayment/views/img/check.png');
        background-size: cover;
        background-position: center center;
    }

    .bm-info__item a {
        display: block;
    }


    .modal-dialog {
        min-width: 900px;
        max-width: 1200px;
        width: auto;
    }


    .bm-payments__wrap {
        display: flex;
        flex-wrap: wrap;

    }

    .bm-payments__item {
        width: 25%;
        padding: 5px;
    }

    .bm-payments__item-inner {
        border: 1px solid #e5e5e5;
        height 250px;
        text-align: center;
        padding: 10px;
    }

    .bm-payments__item img {
        height: 50px;
        margin: 0 auto;
        display: table;
        max-height: 80px;
        margin-bottom: 10px;
    }

    /*///*/


    .modal-content {
        border-radius: 0;
        border-color: transparent;
        padding: 10px 20px;
    }

    .modal-header {
        border: 0;
        /*padding-bottom: 0;*/
    }

    .modal-header h2 {
        margin-top: 0;
        font: 400 25px/1.42857 Open Sans, Helvetica, Arial, sans-serif;
        font-weight: 600 !important;
        margin-bottom: 0;
    }

    .modal-header .close {
        opacity: 1;
        position: absolute;
        right: 40px;
        top: 30px;
        font-weight: 300;
        font-size: 38px;

    }

    .btn-info {
        background: transparent !important;
        border-color: transparent !important;
        cursor: pointer;
    }


    a, a:active, a:focus {
        outline: 0 !important;
    }


    .bootstrap .nav-pills > li > a {
        border-radius: 0px !important;
    }

    .bootstrap > .row > .col-lg-12 {
        display: table;
    }


    .bm-info--small {
        display: flex;
        flex-wrap: wrap;
        border: 1px solid #DDDDDD;
        border-left: 4px solid #0068B4;
        padding: 20px;
    }

    .bm-info--small p {
        margin: 0;
    }


    .bm-info--small__icon {
        margin-right: 15px;
    }

    .blue_gateway_channels td {
        height: 70px;
    }


    .bootstrap label.control-label {
        font-size: 14px;
    }


    .bootstrap .table thead > tr > th span.title_box {
        font-size: 14px;
        color: #000000;
    }

    .bootstrap .table tbody > tr > td:nth-child(2) img {
        padding-right: 16px;
    }

    .bootstrap .table tbody > tr > td:nth-child(2) {
        font-size: 14px;
        color: #000000;
    }


    .bootstrap .table thead > tr > th:nth-child(1) span {
        text-align: left;
        margin-left: 6px;
    }

    .bootstrap .table tbody > tr:hover > td {
        background: transparent;
    }


    .bluepayment-gateways__wrap {
        display: flex;
        flex-wrap: wrap;
        margin-left: -0.3rem;
        margin-right: -0.3rem;
        margin-bottom: 1rem;
    }

    .bluepayment-gateways__item {
        padding: 0.3rem;
        width: 20%;
    }

    @media only screen and (max-width: 768px) {
        .bluepayment-gateways__item {
            width: 33.333%;
        }
    }

    @media only screen and (max-width: 1400px) {
        .bluepayment-gateways__item {
            width: 25%;
        }
    }

    .bluepayment-gateways__img {
        width: 65px;
        display: table;
        margin: 0 auto;
    }

    .bluepayment-gateways__item label {
        border: 2px solid #F1F1F1;
        padding: 0.5rem;
        border-radius: 8px;
        display: flex !important;
        flex-direction: column;
        width: 100%;
        height: 100%;
        transition: all 0.3s ease;
        align-content: flex-start;
        align-items: initial;
        flex-direction: column;
        justify-content: space-between;
        font-weight: 600;
    }

    .bluepayment-gateways__item input:checked ~ label {
        border-color: #005abb;
        transition: all 0.3s ease;
    }

    .bluepayment-gateways__item input {
        display: none;
    }

    .bluepayment-gateways__name {
        text-align: center;
        width: 100%;
        color: black;
        font-size: 12px;
        margin-top: 4px;
        display: block;
        line-height: 1.2;
    }

    .payment-option {
        display: flex;
        align-items: center;
    }

    .payment-option label {
        margin-bottom: 0;
    }

    .payment-brand {
        display: block;
        margin-bottom: 24px;
        max-width: 85px;
    }

    .payment-navigation {
        margin-top: 46px;
        margin-bottom: 16px;
    }


</style>

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

        {if isset($confirmation)}
			<div class="alert alert-success">
                {l s='Konfiguracja zaktualizowana.' mod='bluepayment'}
			</div>
        {/if}

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


                {assign var=getCurrencies value=['PLN','EUR']}

                {if isset($full_payments)}

                    {foreach $getCurrencies as $currency}
						<div class="modal fade" id="Przelew_internetowy_{$currency}" tabindex="-1" role="dialog"
						     aria-labelledby="Przelew_internetowy_{$currency}" aria-hidden="true">
							<div class="modal-dialog" role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h2>
                                            {l s='List of supported banks' mod='bluepayment'}
										</h2>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">

                                        {*										<div class="row bm-payments__wrap" style="width: 100%">*}
                                        {*                                            {foreach $full_payments as $card}*}
                                        {*												<div class="bm-payments__item">*}
                                        {*													<div class="bm-payments__item-inner">*}
                                        {*														<img class="img-responsive" src="{$card['gateway_logo_url']}"*}
                                        {*														     alt="{$card['gateway_name']}">*}
                                        {*														<p>{$card['gateway_name']}</p>*}
                                        {*													</div>*}
                                        {*												</div>*}
                                        {*                                            {/foreach}*}
                                        {*										</div>*}


										<div id="blue_payway" class="bluepayment-gateways">
											<div class="bluepayment-gateways__wrap">
                                                {foreach $full_payments as $card}
													<div class="bluepayment-gateways__item">
                                                        {*														<input type="radio" id="{$row->gateway_name}" class="bluepayment-gateways__radio" name="bluepayment-gateway-gateway-id" value="{$row->gateway_id}" required="required">*}
														<label for="{$card['gateway_name']}">
															<img class="bluepayment-gateways__img"
															     src="{$card['gateway_logo_url']}"
															     alt="{$card['gateway_name']}">
															<span class="bluepayment-gateways__name">{$card['gateway_name']}</span>
														</label>
													</div>
                                                {/foreach}
											</div>
										</div>


									</div>
								</div>
							</div>
						</div>
                    {/foreach}
                {/if}


                {if isset($full_cards)}
                    {foreach $getCurrencies as $currency}
						<div class="modal fade" id="Wirtualny_portfel_{$currency}" tabindex="-1" role="dialog"
						     aria-labelledby="Wirtualny_portfel_{$currency}" aria-hidden="true">
							<div class="modal-dialog" role="document">
								<div class="modal-content">
									<div class="modal-header">
										<h2>
                                            {l s='List of supported wallets' mod='bluepayment'}
										</h2>
										<button type="button" class="close" data-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">&times;</span>
										</button>
									</div>
									<div class="modal-body">


										<div id="blue_payway" class="bluepayment-gateways">
											<div class="bluepayment-gateways__wrap">
                                                {foreach $full_cards as $card}
													<div class="bluepayment-gateways__item">
                                                        {*														<input type="radio" id="{$row->gateway_name}" class="bluepayment-gateways__radio" name="bluepayment-gateway-gateway-id" value="{$row->gateway_id}" required="required">*}
														<label for="{$card['gateway_name']}">
															<img class="bluepayment-gateways__img"
															     src="{$card['gateway_logo_url']}"
															     alt="{$card['gateway_name']}">
															<span class="bluepayment-gateways__name">{$card['gateway_name']}</span>
														</label>
													</div>
                                                {/foreach}
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
                    {/foreach}
                {/if}

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

			console.log($(this));

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


				$('.blue_gateway_channels').find('th, td').filter(':nth-child(2)').append(function () {
					return $(this).next().html();
				}).next().remove();


                {if isset($use_textarea_autosize)}
				$(".textarea-autosize").autosize();
                {/if}
			});
			state_token = '{getAdminToken tab='AdminStates'}';
            {block name="script"}{/block}
		</script>
    {/if}

</div>

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
<div class="panel paymentList">
	<div class="panel-heading">
        {l s='Payment list' mod='bluepayment'}
	</div>
	<div class="row">
        {foreach $list as $l}
            {$l}
        {/foreach}
	</div>


    {if isset($transfer_payments)}
        {foreach $transfer_payments as $key => $currency}
			<div class="modal fade" id="Przelew_internetowy_{$key}" tabindex="-1" role="dialog"
			     aria-labelledby="Przelew_internetowy_{$key}" aria-hidden="true">
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

							<div id="blue_payway" class="bluepayment-gateways">
								<div class="bluepayment-gateways__wrap">
                                    {foreach $currency as $card}
										<div class="bluepayment-gateways__item">
											<label for="{$card['gateway_name']}">
												<img class="bluepayment-gateways__img"
												     src="{$card['gateway_logo_url']}"
												     alt="{$card['gateway_name']}">
												<span class="bluepayment-gateways__name">
																{$card['gateway_name']}
															</span>
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


    {if isset($wallets) and is_array($wallets) }
        {foreach $wallets as $key => $currency}



			<div class="modal fade" id="Wirtualny_portfel_{$key}" tabindex="-1" role="dialog"
			     aria-labelledby="Wirtualny_portfel_{$key}" aria-hidden="true">
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
                                    {foreach $currency as $card}
                                        <div class="bluepayment-gateways__item">
                                            <label for="{$card['gateway_name']}">
                                                <img class="bluepayment-gateways__img"
                                                     src="{$card['gateway_logo_url']}"
                                                     alt="{$card['gateway_name']}">
                                                <span class="bluepayment-gateways__name">
                                                    {$card['gateway_name']}
                                                </span>
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



</div>

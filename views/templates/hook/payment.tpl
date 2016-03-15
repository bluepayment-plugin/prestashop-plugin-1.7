{*
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
*}
<p class="payment_module">
    {if $ps_version < '1.5'}
        <a href="{$module_link}" title="{l s='Pay with Blue Media system' mod='bluepayment'}">
            <span>
                <img src="{$module_dir}logo.png" alt="{l s='Pay with Blue Media system' mod='bluepayment'}" />
                {l s='Pay with Blue Media system' mod='bluepayment'}
                ({l s='You will be redirected to the Blue Media secure system payment after submitting the order.' mod='bluepayment'})
            </span>
        </a>
    {else}
        <a class="logo_background" href="{$module_link}" title="{l s='Pay with Blue Media system' mod='bluepayment'}">
            &nbsp;{l s='Pay with Blue Media system' mod='bluepayment'}
            <span>({l s='You will be redirected to the Blue Media secure system payment after submitting the order.' mod='bluepayment'})</span>
        </a>
    {/if}
</p>
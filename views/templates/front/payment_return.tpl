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

{capture name=path}
    <span class="navigation-pipe">
        {$navigationPipe}
    </span>
    <span class="navigation_page">
        {l s='Podziękowanie'}
    </span>
{/capture}

{if $hash_valid == false}
    <span class="alert-warning">
        {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='bluepayment'}
        <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='bluepayment'}</a>.
    </span>
{else}
    <h2>Dziękujemy za złożenie zamówienia {$order->reference}</h2>
    Możesz obejrzeć swoje zamówienie i pobrać fakturę na stronie <a href="{$link->getPageLink('history', true)}">"Historia zamówień"</a> logując się na swoje
    konto klienta za pomocą strony <a href="{$link->getPageLink('my-account', true)}">"Moje konto"</a> w naszym sklepie.
    Jeśli posiadasz konto gościa, możesz śledzić swoje zamówienie w sekcji <a href="{$link->getPageLink('guest-tracking', true)}">"Śledzenie zamówienia"</a>.
{/if}
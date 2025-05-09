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
 * @copyright      Copyright (c) 2015-2025
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}


<div class="bm-info--small bm-info--dev spacing-bottom">
    <img width="22" class="bm-info--small__icon img-fluid" src="{$src_img|escape:'html':'UTF-8'}/info.svg" alt="Info" />

    <p>{l s='The Ads service is a complex solution that lets you advertise products from your store with Google Ads, Meta Ads and Microsoft Ads. The service is fully integrated with Presta Shop, so you don\'t need to make any changes to your store. All you need to do is enable campaign tracking via Autopay and publish a product stream (product feed). You can find more information about the advertising service' mod='bluepayment'}

        <a target="_blank" href="https://autopay.pl/oferta/reklama-produktowow-w-google-i-meta">
            {l s='HERE.' mod='bluepayment'}
        </a>
    </p>
</div>

{if isset($is_disable_product_feed) && $is_disable_product_feed}
    <div class="form-group">
        <label class="control-label col-lg-3">
            {l s='Link do generowania feed-u przez CRON' mod='bluepayment'}
        </label>
        <div class="col-lg-9">
            {foreach $product_feed_cron_link as $cron_link}
                <div class="input-group">
                    <input type="text" class="form-control" value="{$cron_link}" readonly>
                    <span class="input-group-btn">
                        <a href="{$cron_link}" target="_blank" class="btn btn-primary">
                            <i class="icon-external-link mr-2"></i> {l s='Otwórz' mod='bluepayment'}
                        </a>
                    </span>
                </div>
            {/foreach}
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">
            {l s='Link do pliku XML' mod='bluepayment'}
        </label>
        <div class="col-lg-9">
            {if empty($product_feed_file_link)}
              <div class="alert alert-warning">
                {l s='Wygeneruj feed przez CRON, potem kliknij w button "Refresh"' mod='bluepayment'}
              </div>
              <a href="#payment-options" onClick="window.location.reload();" class="btn btn-primary">{l s='Refresh' mod='bluepayment'}</a>
            {/if}
            {foreach $product_feed_file_link as $file_link}
                <div class="input-group">
                    <input type="text" class="form-control" value="{$file_link}" readonly>
                    <span class="input-group-btn">
                        <a href="{$file_link}" target="_blank" class="btn btn-primary">
                            <i class="icon-external-link mr-2"></i>
                            {l s='Otwórz' mod='bluepayment'}
                        </a>
                    </span>
                </div>
            {/foreach}
        </div>
    </div>
{/if}
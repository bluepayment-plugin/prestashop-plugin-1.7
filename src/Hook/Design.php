<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Hook;

use BlueMedia\BluePayment\Model\Data\Configuration;
use Configuration as Cfg;
use BluePayment\Until\Helper;
use BluePayment\Config\Config;

class Design extends AbstractHook
{
    const AVAILABLE_HOOKS = [
        'actionFrontControllerSetMedia',
        'displayBeforeBodyClosingTag',
        'displayProductPriceBlock',
        'displayBanner',
        'displayFooterBefore',
        'displayProductAdditionalInfo',
        'displayLeftColumn',
        'displayRightColumn',
        'displayShoppingCartFooter',
        'displayShoppingCart'
    ];

    public $show_apc = true;


    /**
     * @codeCoverageIgnore
     * Header hooks
     */
    public function actionFrontControllerSetMedia()
    {

        \Media::addJsDef([
            'bluepayment_env' => (int)Cfg::get($this->module->name_upper . '_TEST_ENV') === 1 ? 'TEST' : 'PRODUCTION',
            'asset_path' => $this->module->getPathUrl() . 'views/',
            'change_payment' => $this->module->l('change'),
            'read_more' => $this->module->l('read more'),
            'get_regulations_url' => $this->context->link->getModuleLink('bluepayment', 'regulationsGet', [], true),
        ]);
        $currency = $this->context->currency->iso_code;

        $serviceId = Helper::parseConfigByCurrency($this->module->name_upper . Config::SERVICE_PARTNER_ID, $currency);


        $path = 'modules/' . $this->module->name . '/views/';

        $this->context->controller->registerStylesheet('bm-front-css', $path . 'css/front.css');
        $this->context->controller->registerStylesheet('bm-front-css', $path . 'css/apc.css');
        $this->context->controller->registerJavascript('bm-front-js', $path . 'js/front.min.js');
        $this->context->controller->registerJavascript('bm-blik-js', $path . 'js/blik_v3.js');
        $this->context->controller->registerJavascript('bm-gpay-js', $path . 'js/gpay.js');
        if ($this->configuration->get($this->module->name_upper . '_APC_ENABLED')) {
            if ($this->configuration->get($this->module->name_upper . '_APC_HIDDEN_MODE')) {
                if(!\Tools::getValue('test_autopay')){
                    $this->show_apc = false;
                }
            }
            if ($this->show_apc) {
                $this->context->controller->registerJavascript('bm-apc-js', $path . 'js/autopay.js');
                $this->context->controller->registerJavascript('apc-sdk', 'https://oapi-accept.autopay.pl/checkout/www/sdk/' . $serviceId, array('media' => 'all', 'priority' => 10, 'inline' => true, 'server' => 'remote'));
            }
        }
    }

    /**
     * Add analytics Gtag
     * @param $params
     * @return void|null
     */
    public function displayProductPriceBlock($params)
    {
        if ($params['type'] === 'before_price') {
            $product = $params['product'];
            $brand = '';

            if (isset($product['id_manufacturer'])) {
                $brand = \Manufacturer::getNameById($product['id_manufacturer']);
            }

            $this->context->smarty->assign([
                'ga_product_id' => $product['id'],
                'ga_product_name' => $product['name'],
                'ga_product_brand' => $brand,
                'ga_product_cat' => $product['category_name'],
                'ga_product_price' => $product['price'],
            ]);

            return $this->module->fetch('module:bluepayment/views/templates/hook/ga_listing.tpl');
        }

        return null;
    }


    /**
     * Adds promoted payments to the top of the page
     * @return string|null
     */
    public function displayBanner(): ?string
    {
        if ($this->configuration->get($this->module->name_upper . '_PROMO_HEADER')) {
            $this->getSmartyAssets();
            return $this->module->fetch('module:bluepayment/views/templates/hook/labels/header.tpl');
        }
        return null;
    }

    /**
     * Adds promoted payments above the footer
     * @return string|null
     */
    public function hookDisplayFooterBefore(): ?string
    {
        if ($this->configuration->get($this->module->name_upper . '_PROMO_FOOTER')) {
            $this->getSmartyAssets();
            return $this->module->fetch('module:bluepayment/views/templates/hook/labels/footer.tpl');
        }
        return null;
    }


    /**
     * Adds promoted payments under the buttons in the product page
     * @return string|null
     */
    public function displayProductAdditionalInfo($params): ?string
    {
        $result = '';
        if ($this->configuration->get($this->module->name_upper . '_APC_ENABLED')) {
            $this->getSmartyAssets('autopay', $params);
            $result .= $this->module->fetch('module:bluepayment/views/templates/hook/labels/autopay.tpl');
        }
        if ($this->configuration->get($this->module->name_upper . '_PROMO_PRODUCT')) {
            $this->getSmartyAssets('product');
            $result .= $this->module->fetch('module:bluepayment/views/templates/hook/labels/product.tpl');
        }

        return $result;
    }

    /**
     * Adds promoted payments sidebar
     * @return string|null
     */
    public function getSidebarPromo(): ?string
    {
        if ($this->configuration->get($this->module->name_upper . '_PROMO_LISTING')) {
            $this->getSmartyAssets('sidebar');
            return $this->module->fetch('module:bluepayment/views/templates/hook/labels/labels.tpl');
        }
        return null;
    }


    /**
     * @codeCoverageIgnore
     * Adds promoted payments in the left column on the category subpage
     */
    public function displayLeftColumn()
    {
        $this->getSidebarPromo();
    }

    /**
     * @codeCoverageIgnore
     * Adds promoted payments in the right column on the category subpage
     */
    public function displayRightColumn()
    {
        $this->getSidebarPromo();
    }

    /**
     * Adds promoted payments in the shopping cart under products
     * @return string|null
     */
    public function displayShoppingCartFooter(): ?string
    {
        if ($this->configuration->get($this->module->name_upper . '_PROMO_CART')) {
            $this->getSmartyAssets('cart');
            return $this->module->fetch('module:bluepayment/views/templates/hook/labels/labels.tpl');
        }
        return null;
    }

    /**
     * Adds promoted payments in the shopping cart under products
     * @return string|null
     */
    public function displayShoppingCart(): ?string
    {
        $this->getSmartyAssets('autopay');
        return $this->module->fetch('module:bluepayment/views/templates/hook/labels/autopay-cart.tpl');
    }

    /**
     * Gtag data
     * @return string
     */
    public function displayBeforeBodyClosingTag(): string
    {
        $controller = \Tools::getValue('controller');

        $tracking_id = false;
        $secret_key = false;

        if (Cfg::get('BLUEPAYMENT_GA_TRACKER_ID')) {
            $tracking_id = Cfg::get('BLUEPAYMENT_GA_TRACKER_ID');
        } elseif (Cfg::get('BLUEPAYMENT_GA4_TRACKER_ID') && Cfg::get('BLUEPAYMENT_GA4_SECRET')) {
            $tracking_id = Cfg::get('BLUEPAYMENT_GA4_TRACKER_ID');
            $secret_key = Cfg::get('BLUEPAYMENT_GA4_SECRET');
        }

        $this->context->smarty->assign([
            'tracking_id' => $tracking_id,
            'tracking_secret_key' => $secret_key,
            'controller' => $controller,
            'bm_ajax_controller' => $this->context->link->getModuleLink(
                $this->module->name,
                'ajax',
                array('ajax' => 1)
            )
        ]);

        if ($controller == 'cart') {
            $this->context->smarty->assign([
                'products' => $this->context->cart->getProducts(false, false, null, false),
            ]);
        } elseif ($controller == 'order') {
            $coupons_array = [];
            $coupons_list = '';

            if ($this->context->cart->getCartRules()) {
                foreach ($this->context->cart->getCartRules() as $coupon) {
                    $coupons_array[] = $coupon['name'];
                }
                $coupons_list = implode(", ", $coupons_array);
            }

            $this->context->smarty->assign([
                'products' => $this->context->cart->getProducts(true),
                'coupons' => $coupons_list,
                'ga4_tracking_id' => Cfg::get('BLUEPAYMENT_GA4_TRACKER_ID') ?? false,
                'ga4_secret' => Cfg::get('BLUEPAYMENT_GA4_SECRET') ?? false,
            ]);
        }

        return $this->module->display(
            $this->module->getPathUrl(),
            'views/templates/hook/gtag.tpl'
        );
    }


    /**
     * Get smarty assets
     *
     * @param string $type
     * @return void
     * @codeCoverageIgnore
     */
    public function getSmartyAssets(string $type = 'main', $params = null)
    {
        $payLater = Cfg::get($this->module->name_upper . '_PROMO_PAY_LATER');
        $instalment = Cfg::get($this->module->name_upper . '_PROMO_INSTALMENTS');
        $matchInstalments = Cfg::get($this->module->name_upper . '_PROMO_MATCHED_INSTALMENTS');
        $promoCheckout = Cfg::get($this->module->name_upper . '_PROMO_CHECKOUT');
        $userId = $this->context->customer->id;
        $base = __PS_BASE_URI__;
        if (is_null($this->context->cart->id)) {
            $this->context->cart->add();
            $this->context->cookie->__set('id_cart', $this->context->cart->id);
        }
        $id_product_attribute = null;
        $id_product = null;
        if ($params) {
            if (isset($params['product']['id'])) {
                $id_product = $params['product']['id'];
            }
            if (isset($params['product']['id_product_attribute'])) {
                $id_product_attribute = $params['product']['id_product_attribute'];
            }
        }
        if(\Tools::getValue('autopay_theme')){
            $apc_theme = \Tools::getValue('autopay_theme');
        }
        else{
            $apc_theme = $this->configuration->get($this->module->name_upper . '_APC_BUTTON_THEME');
        }
        if(\Tools::getValue('autopay_rounded')){
            $apc_rounded = \Tools::getValue('autopay_rounded');
        }
        else{
            $apc_rounded = $this->configuration->get($this->module->name_upper . '_APC_BUTTON_ROUNDED');
        }
        if(\Tools::getValue('autopay_fullwidth')){
            $apc_fullwidth = \Tools::getValue('autopay_fullwidth');
        }
        else{
            $apc_fullwidth = $this->configuration->get($this->module->name_upper . '_APC_BUTTON_FULLWIDTH');
        }

        $this->context->smarty->assign(
            [
                'bm_assets_images' => $this->module->getAssetImages(),
                'bm_instalment' => $instalment,
                'bm_pay_later' => $payLater,
                'bm_matched_instalments' => $matchInstalments,
                'bm_promo_checkout' => $promoCheckout,
                'bm_promo_type' => $type,
                'userId' => $userId,
                'cartId' => $this->context->cart->id,
                'minimum_order_value' => $this->configuration->get('PS_PURCHASE_MINIMUM'),
                'apc_merchantid' => $this->configuration->get($this->module->name_upper . '_APC_MERCHANTID'),
                'apc_button_theme' => $apc_theme,
                'apc_button_fullwidth' => $apc_fullwidth,
                'apc_button_rounded' => $apc_rounded,
                'apc_button_margintop' => $this->configuration->get($this->module->name_upper . '_APC_BUTTON_MARGINTOP'),
                'apc_button_marginbottom' => $this->configuration->get($this->module->name_upper . '_APC_BUTTON_MARGINBOTTOM'),
                'currency' => $this->context->currency->id,
                'currency_iso' => $this->context->currency->iso_code,
                'base' => $base,
                'id_product_attribute' => $id_product_attribute,
                'id_product' => $id_product
            ]
        );
    }
}

<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Service\PaymentMethods;

use BluePayment\Api\BlueGatewayTransfers;
use BluePayment\Config\Config;
use BluePayment\Until\Helper;
use Configuration as Cfg;
use Context;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use BluePayment\Api\BlueGatewayChannels;
use Cart;

class BlikLater implements GatewayType
{
    public function getPaymentOption(
        \BluePayment $module,
        array $data = []
    ): PaymentOption {
        $moduleLink = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'payment',
            [],
            true
        );

        $smartneyMerchantInfo = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'merchantInfo',
            [],
            true
        );
        $smartneyLinkCharge = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'chargeSmartney',
            [],
            true
        );

        Context::getContext()->smarty->assign([
            'smartney_merchantInfo' => $smartneyMerchantInfo,
            'smartney_moduleLinkCharge' => $smartneyLinkCharge,
        ]);


        $option = new PaymentOption();
        $option
            ->setInputs([
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway',
                    'value' => Config::GATEWAY_ID_BLIK_LATER,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway_id',
                    'value' => Config::GATEWAY_ID_BLIK_LATER,
                ],
            ])
            ->setCallToActionText($data['gateway_name'])
            ->setAction($moduleLink)
            ->setLogo($data['gateway_logo_url'])
            ->setAdditionalInformation(
                $module->fetch('module:bluepayment/views/templates/hook/paymentBlikLater.tpl')
            );

        return $option;
    }

    /**
     * @return bool
     */
    public function isActive($cart_total = null): bool
    {
        $iso_code = Helper::getIsoFromContext(Context::getContext());

        $blikLater = BlueGatewayChannels::getByGatewayIdAndCurrency(
            Config::GATEWAY_ID_BLIK_LATER,
            $iso_code
        );

        if (!$cart_total) {
            $cart_total = Context::getContext()->cart->getOrderTotal(true, Cart::BOTH);
        }

        return $blikLater->id
            && (float) $cart_total >= (float) $blikLater->min_amount
            && (float) $cart_total <= (float) $blikLater->max_amount;
    }
}

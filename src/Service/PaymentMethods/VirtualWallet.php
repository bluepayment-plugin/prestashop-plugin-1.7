<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html.
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Service\PaymentMethods;

use BluePayment\Api\BlueGatewayTransfers;
use BluePayment\Until\Helper;
use Configuration as Config;
use Context;
use Module;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class VirtualWallet implements GatewayType
{
    public function getPaymentOption(
        \Module $module,
        array $data = []
    ): PaymentOption {
        $moduleLink = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'payment',
            [],
            true
        );
        $walletMerchantInfo = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'merchantInfo',
            [],
            true
        );
        $gpay_moduleLinkCharge = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'chargeGPay',
            [],
            true
        );

        $gpayRedirect = false;
        if (Config::get($module->name_upper . '_GPAY_REDIRECT')) {
            $gpayRedirect = true;
        }

        $googlePay = BlueGatewayTransfers::gatewayIsActive(
            GATEWAY_ID_GOOGLE_PAY,
            Context::getContext()->currency->iso_code
        );
        $applePay = BlueGatewayTransfers::gatewayIsActive(
            GATEWAY_ID_APPLE_PAY,
            Context::getContext()->currency->iso_code
        );

        Context::getContext()->smarty->assign([
            'wallet_merchantInfo' => $walletMerchantInfo,
            'gpay_redirect' => $gpayRedirect,
            'gpay_moduleLinkCharge' => $gpay_moduleLinkCharge,
            'googlePay' => $googlePay,
            'applePay' => $applePay,
        ]);

        $option = new PaymentOption();
        $option->setCallToActionText($module->l('Virtual wallet'))
            ->setAction($moduleLink)
            ->setInputs([
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway',
                    'value' => 0,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'gpay_get_merchant_info',
                    'value' => $walletMerchantInfo,
                ],
            ])
            ->setLogo(Helper::getBrandLogo())
            ->setAdditionalInformation(
                $module->fetch('module:bluepayment/views/templates/hook/wallet.tpl')
            );

        return $option;
    }

    public function isActive(): bool
    {
        $gpay = (bool) BlueGatewayTransfers::gatewayIsActive(
            GATEWAY_ID_GOOGLE_PAY,
            Context::getContext()->currency->iso_code
        );

        $applePay = (bool) BlueGatewayTransfers::gatewayIsActive(
            GATEWAY_ID_APPLE_PAY,
            Context::getContext()->currency->iso_code
        );

        if ($gpay || $applePay) {
            return true;
        }

        return false;
    }
}

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

namespace BluePayment\Service\PaymentMethods;

use BluePayment\Api\BlueGatewayTransfers;
use Configuration as Config;
use Context;
use Module;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Tools;
use Cart;

class Smartney implements GatewayType
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
        $option->setCallToActionText($module->l($data['gateway_name']))
            ->setAction($moduleLink)
            ->setInputs([
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway',
                    'value' => GATEWAY_ID_SMARTNEY,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway_id',
                    'value' => GATEWAY_ID_SMARTNEY,
                ],
            ])
            ->setLogo($data['gateway_logo_url'])
            ->setAdditionalInformation(
                $module->fetch('module:bluepayment/views/templates/hook/paymentRedirectSmartney.tpl')
            );

        return $option;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        $smartney = (bool)BlueGatewayTransfers::gatewayIsActive(
            GATEWAY_ID_SMARTNEY,
            Context::getContext()->currency->iso_code
        );

        if (
            $smartney
            && (float)Context::getContext()->cart->getOrderTotal(true, Cart::BOTH) >= (float)SMARTNEY_MIN_AMOUNT
            && (float)Context::getContext()->cart->getOrderTotal(true, Cart::BOTH) <= (float)SMARTNEY_MAX_AMOUNT
        ) {
            return true;
        }

        return false;
    }
}

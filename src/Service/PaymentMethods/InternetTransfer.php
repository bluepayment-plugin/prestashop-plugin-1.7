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

use Configuration as Config;
use Context;
use Module;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Tools;
use BluePayment\Until\Helper;
use Shop;
use Db;

class InternetTransfer implements GatewayType
{
    public function getPaymentOption(
        \Module $module,
        array $data = []
    ): PaymentOption {

        $paymentName = Config::get(
            $module->name_upper . '_PAYMENT_GROUP_NAME',
            Context::getContext()->language->id
        );

        $cardIdTime = Context::getContext()->cart->id . '-' . time();

        $moduleLink = Context::getContext()->link->getModuleLink(
            'bluepayment',
            'payment',
            [],
            true
        );

        /// Get all transfers
        if(!is_object(Context::getContext()->currency)){
            $currency = Context::getContext()->currency['iso_code'];
        }
        else{
            $currency = Context::getContext()->currency->iso_code;
        }
        $idShop = Context::getContext()->shop->id;
        $gatewayTransfer = new \DbQuery();
        $gatewayTransfer->from('blue_gateway_transfers', 'gt');
        $gatewayTransfer->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $gatewayTransfer->where('gt.gateway_id NOT IN (' . Helper::getGatewaysList() . ')');
        $gatewayTransfer->where('gt.gateway_status = 1');
        $gatewayTransfer->where('gt.gateway_currency = "' . pSql($currency) . '"');

        if (Shop::isFeatureActive()) {
            $gatewayTransfer->where('gts.id_shop = ' . (int)$idShop);
        }

        $gatewayTransfer->select('*');
        $gatewayTransfer = Db::getInstance()->executeS($gatewayTransfer);

        Context::getContext()->smarty->assign([
            'selectPayWay' => Config::get($module->name_upper . '_SHOW_PAYWAY'),
            'start_payment_translation' => $module->l('Start payment'),
            'order_subject_to_payment_obligation_translation' => $module->l('Order with the obligation to pay'),
            'img_transfers' => Helper::getImgPayments('transfers', $currency, $idShop),
            'gateway_transfers' => $gatewayTransfer,
        ]);
        $option = new PaymentOption();
        $option->setCallToActionText($paymentName)
            ->setAction($moduleLink)
            ->setInputs([
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_gateway',
                    'value' => '0',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment-hidden-psd2-regulation-id',
                    'value' => '0',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'bluepayment_cart_id',
                    'value' => $cardIdTime,
                ],
            ])
            ->setLogo(Helper::getBrandLogo())
            ->setAdditionalInformation(
                $module->fetch('module:bluepayment/views/templates/hook/payment.tpl')
            );

        return $option;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return true;
    }
}

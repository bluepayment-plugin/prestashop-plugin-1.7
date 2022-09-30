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
use BluePayment\Api\BlueGatewayChannels;
use BluePayment\Until\Helper;
use Configuration as Config;
use Context;
use Module;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Configuration as Cfg;
use Db;
use Shop;

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

        if(!is_object(Context::getContext()->currency)){
            $currency = Context::getContext()->currency['iso_code'];
        }
        else{
            $currency = Context::getContext()->currency->iso_code;
        }
        $googlePay = $this->checkIfActiveSubChannel(GATEWAY_ID_GOOGLE_PAY, $currency);
        $applePay = $this->checkIfActiveSubChannel(GATEWAY_ID_APPLE_PAY, $currency);

        $idShop = Context::getContext()->shop->id;

        $gatewayWallet = new \DbQuery();
        $gatewayWallet->from('blue_gateway_transfers', 'gt');
        $gatewayWallet->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $gatewayWallet->where('gt.gateway_id IN (' . Helper::getWalletsList() . ')');
        $gatewayWallet->where('gt.gateway_status = 1');
        $gatewayWallet->where('gt.gateway_currency = "' . pSql($currency) . '"');

        if (Shop::isFeatureActive()) {
            $gatewayWallet->where('gts.id_shop = ' . (int)$idShop);
        }

        $gatewayWallet->select('*');
        $gatewayWallet = Db::getInstance()->executeS($gatewayWallet);

        Context::getContext()->smarty->assign([
            'wallet_merchantInfo' => $walletMerchantInfo,
            'gpayRedirect' => $gpayRedirect,
            'gpay_moduleLinkCharge' => $gpay_moduleLinkCharge,
            'googlePay' => $googlePay,
            'applePay' => $applePay,
            'img_wallets' => Helper::getImgPayments('wallet', $currency, $idShop),
            'gateway_wallets' => $gatewayWallet,
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

    /**
     * Function check if active Gpay or Apple Pay
     * @return bool
     */
    public function isActive(): bool
    {
        $iso_code = Helper::getIsoFromContext(Context::getContext());

        $googlePay = $this->checkIfActiveSubChannel(GATEWAY_ID_GOOGLE_PAY, $iso_code);
        $applePay = $this->checkIfActiveSubChannel(GATEWAY_ID_APPLE_PAY, $iso_code);

        return $googlePay || $applePay;
    }


    public function checkIfActiveSubChannel($gatewayId, $currency): bool
    {
        return BlueGatewayChannels::isChannelActive(
            $gatewayId,
            $currency
        );
    }
}

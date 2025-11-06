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

namespace BluePayment\Traits;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BlueMedia\OnlinePayments\Model\Gateway as GatewayModel;
use BluePayment\Config\Config;

trait MultilingualGatewayTrait
{
    /**
     * Creates multilingual Transfer Payment Option Gateway
     *
     * @return GatewayModel
     */
    public function createMultilingualTransferGateway(): GatewayModel
    {
        $gateway = new GatewayModel();
        $gateway->setGatewayId(Config::GATEWAY_ID_TRANSFER);
        $gateway->setGatewayType('1');
        $gateway->setGatewayPayment('1');
        $gateway->setIconUrl($this->module->getAssetImages() . $this->getPaymentsIcon());

        $languages = \Language::getLanguages(false);

        foreach ($languages as $language) {
            $langId = (int) $language['id_lang'];
            $locale = isset($language['locale']) && $language['locale'] ? $language['locale'] : $language['iso_code'];

            $translator = $this->module->getTranslator();
            $name = $translator->trans('Online Transfer', [], 'Modules.Bluepayment', $locale);
            $desc = $translator->trans('Fast and secure online bank transfer', [], 'Modules.Bluepayment', $locale);
            $short = $translator->trans('Online Transfer', [], 'Modules.Bluepayment', $locale);
            $btn = $translator->trans('Pay by transfer', [], 'Modules.Bluepayment', $locale);

            $gateway->setGatewayName((string) $langId, $name);
            $gateway->setDescription((string) $langId, $desc);
            $gateway->setShortDescription((string) $langId, $short);
            $gateway->setButtonTitle((string) $langId, $btn);
        }

        $gateway->setBankName('Online Transfer');

        return $gateway;
    }

    /**
     * Creates multilingual Wallet Payment Option Gateway
     *
     * @return GatewayModel
     */
    public function createMultilingualWalletGateway(): GatewayModel
    {
        $gateway = new GatewayModel();
        $gateway->setGatewayId(Config::GATEWAY_ID_WALLET);
        $gateway->setGatewayType('1');
        $gateway->setGatewayPayment('1');
        $gateway->setIconUrl($this->module->getAssetImages() . $this->getCardsIcon());

        $languages = \Language::getLanguages(false);

        foreach ($languages as $language) {
            $langId = (int) $language['id_lang'];
            $locale = isset($language['locale']) && $language['locale'] ? $language['locale'] : $language['iso_code'];

            $translator = $this->module->getTranslator();
            $name = $translator->trans('Digital Wallet', [], 'Modules.Bluepayment', $locale);
            $desc = $translator->trans('Mobile payments and digital wallets', [], 'Modules.Bluepayment', $locale);
            $short = $translator->trans('Digital Wallet', [], 'Modules.Bluepayment', $locale);
            $btn = $translator->trans('Pay with wallet', [], 'Modules.Bluepayment', $locale);

            $gateway->setGatewayName((string) $langId, $name);
            $gateway->setDescription((string) $langId, $desc);
            $gateway->setShortDescription((string) $langId, $short);
            $gateway->setButtonTitle((string) $langId, $btn);
        }

        $gateway->setBankName('Digital Wallet');

        return $gateway;
    }

    /**
     * Maps Gateway model multilingual data to BlueGatewayChannels format
     *
     * @param GatewayModel $gateway
     *
     * @return array Array of multilingual data ready for BlueGatewayChannels
     */
    public function mapGatewayToMultilingualData(GatewayModel $gateway): array
    {
        $multilingualData = [];
        $languages = \Language::getLanguages(false);

        foreach ($languages as $language) {
            $langId = (int) $language['id_lang'];

            $multilingualData[$langId] = [
                'gateway_name' => $gateway->getGatewayNameForLanguage((string) $langId) ?: '',
                'bank_name' => $gateway->getBankName() ?: '',
                'gateway_description' => $gateway->getDescriptionForLanguage((string) $langId) ?: '',
                'group_title' => $gateway->getGroupTitleForLanguage((string) $langId) ?: '',
                'group_short_description' => $gateway->getGroupShortDescriptionForLanguage((string) $langId) ?: '',
                'group_description' => $gateway->getGroupDescriptionForLanguage((string) $langId) ?: '',
                'button_title' => $gateway->getButtonTitleForLanguage((string) $langId) ?: '',
                'description' => $gateway->getDescriptionForLanguage((string) $langId) ?: '',
                'short_description' => $gateway->getShortDescriptionForLanguage((string) $langId) ?: '',
                'description_url' => $gateway->getDescriptionUrlForLanguage((string) $langId) ?: '',
            ];
        }

        return $multilingualData;
    }

    /**
     * Creates multilingual data array from simple text values
     * Useful for creating fallback translations
     *
     * @param string $gatewayName
     * @param string $bankName
     * @param string $description
     *
     * @return array
     */
    public function createFallbackMultilingualData(
        string $gatewayName,
        string $bankName = '',
        string $description = ''
    ): array {
        $multilingualData = [];
        $languages = \Language::getLanguages(false);

        foreach ($languages as $language) {
            $langId = (int) $language['id_lang'];

            $multilingualData[$langId] = [
                'gateway_name' => $gatewayName,
                'bank_name' => $bankName ?: $gatewayName,
                'gateway_description' => $description,
                'group_title' => '',
                'group_short_description' => '',
                'group_description' => '',
                'button_title' => '',
                'description' => $description,
                'short_description' => '',
            ];
        }

        return $multilingualData;
    }

    /**
     * Get payments icon filename
     *
     * @return string
     */
    protected function getPaymentsIcon(): string
    {
        return 'payments.png';
    }

    /**
     * Get cards icon filename
     *
     * @return string
     */
    protected function getCardsIcon(): string
    {
        return 'cards.png';
    }
}

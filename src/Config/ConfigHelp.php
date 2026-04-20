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

namespace BluePayment\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ConfigHelp
{
    public const HELP_LINKS_PL = [
        'implementation' => 'https://developers.autopay.pl/online/wdrozenie-krok-po-kroku',
        'configuration' => 'https://developers.autopay.pl/online/wtyczki/prestashop-1-7?utm_campaign=help&utm_source=prestashop_panel&mtm_medium=text_link#konfiguracja',
        'update' => 'https://developers.autopay.pl/online/wtyczki/prestashop-1-7?utm_campaign=help&utm_source=prestashop_panel&mtm_medium=text_link#aktualizacja',
        'contact' => 'https://developers.autopay.pl/kontakt/plugins/presta?utm_campaign=help&utm_source=prestashop_panel&mtm_medium=text_link',
    ];

    public const HELP_LINKS_EN = [
        'implementation' => 'https://developers.autopay.pl/en/online/wdrozenie-krok-po-kroku-en',
        'configuration' => 'https://developers.autopay.pl/en/online/plugins/prestashop-1-7#configuration',
        'update' => 'https://developers.autopay.pl/en/online/plugins/prestashop-1-7#update',
        'contact' => 'https://developers.autopay.pl/kontakt/plugins/presta?utm_campaign=help&utm_source=prestashop_panel&mtm_medium=text_link',
    ];

    public function getLinksByIsoCode($isoCode)
    {
        switch (strtoupper($isoCode)) {
            case 'PL':
                return self::HELP_LINKS_PL;
            default:
                return self::HELP_LINKS_EN;
        }
    }

    public function getHelperImagesByIsoCode($isoCode, $baseImgPath)
    {
        $isoCodeUpper = strtoupper($isoCode);
        $paymentsPath = $baseImgPath . '/helpers/payments/';

        switch ($isoCodeUpper) {
            case 'ES':
                return [
                    'helper_name' => $paymentsPath . 'es-helper-name.webp',
                    'helper_name2' => $paymentsPath . 'es-helper-name2.webp',
                    'helper_payment' => $paymentsPath . 'es-helper-payment.webp',
                    'helper_payment2' => $paymentsPath . 'es-helper-payment2.webp',
                ];
            case 'IT':
                return [
                    'helper_name' => $paymentsPath . 'it-helper-name.webp',
                    'helper_name2' => $paymentsPath . 'it-helper-name2.webp',
                    'helper_payment' => $paymentsPath . 'it-helper-payment.webp',
                    'helper_payment2' => $paymentsPath . 'it-helper-payment2.webp',
                ];
            case 'DE':
                return [
                    'helper_name' => $paymentsPath . 'de-helper-name.png',
                    'helper_name2' => $paymentsPath . 'de-helper-name2.png',
                    'helper_payment' => $paymentsPath . 'de-helper-payment.png',
                    'helper_payment2' => $paymentsPath . 'de-helper-payment2.png',
                ];
            case 'EN':
                return [
                    'helper_name' => $paymentsPath . 'en-helper-name.png',
                    'helper_name2' => $paymentsPath . 'en-helper-name2.png',
                    'helper_payment' => $paymentsPath . 'en-helper-payment.png',
                    'helper_payment2' => $paymentsPath . 'en-helper-payment2.png',
                ];
            default:
                return [
                    'helper_name' => $paymentsPath . 'helper-name.png',
                    'helper_name2' => $paymentsPath . 'helper-name2.png',
                    'helper_payment' => $paymentsPath . 'helper-payment.png',
                    'helper_payment2' => $paymentsPath . 'helper-payment2.png',
                ];
        }
    }
}

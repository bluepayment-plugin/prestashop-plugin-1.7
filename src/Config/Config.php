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

namespace BluePayment\Config;

class Config
{
    const BM_IMAGES_PATH = _MODULE_DIR_ . 'bluepayment/views/img/';
    const SERVICE_PARTNER_ID = '_SERVICE_PARTNER_ID';
    const SHARED_KEY = '_SHARED_KEY';

    const HASH_SEPARATOR = '|';
    const ALIOR_MIN_AMOUNT = 50;
    const ALIOR_MAX_AMOUNT = 7750;
    const SMARTNEY_MIN_AMOUNT = 100;
    const SMARTNEY_MAX_AMOUNT = 2500;
    const BLIK_CODE_LENGTH = 6;
    const DEFAULT_PAYMENT_FORM_LANGUAGE = 'pl';

    const GATEWAY_ID_ALIOR = 1506;
    const GATEWAY_ID_CARD = 1500;
    const GATEWAY_ID_GOOGLE_PAY = 1512;
    const GATEWAY_ID_APPLE_PAY = 1513;
    const GATEWAY_ID_SMARTNEY = 700;
    const GATEWAY_ID_BLIK = 509;
    const GATEWAY_ID_WALLET = 999;
    const GATEWAY_ID_TRANSFER = 9999;

    // Amplitude events
    const PLUGIN_ACTIVATED = 'plugin activated';
    const PLUGIN_DEACTIVATED = 'plugin deactivated';
    const PLUGIN_INSTALLED = 'plugin installed';
    const PLUGIN_UNINSTALLED = 'plugin uninstalled';
    const PLUGIN_VERSION = 'plugin version';
    const PLUGIN_UPDATED = 'plugin updated';

    const PLUGIN_AUTH = 'plugin authorized';
    const API_AUTHENTICATION_SUCCESS = 'authorization completed';
    const API_AUTHENTICATION_FAILED = 'authorization failed';

    public static function getSdk(): void
    {
        if(file_exists(__DIR__ . '/../../sdk/index.php')) {
            require_once __DIR__ . '/../../sdk/index.php';
        }
    }

}

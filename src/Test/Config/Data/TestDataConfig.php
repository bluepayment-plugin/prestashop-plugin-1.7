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

namespace BluePayment\Test\Config\Data;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class TestDataConfig
{
    public const TEST_CUSTOMER_ID_KEY = 'BLUEPAYMENT_TEST_CUSTOMER_ID';
    public const TEST_ADDRESS_ID_KEY = 'BLUEPAYMENT_TEST_ADDRESS_ID';
    public const TEST_PRODUCT_ID_KEY = 'BLUEPAYMENT_TEST_PRODUCT_ID';

    public const BLIK_SUCCESS_CODE = '111112';
    public const BLIK_INVALID_CODE = '111121';
    public const BLIK_EXPIRED_CODE = '111122';
    public const BLIK_USED_CODE = '111123';
    public const BLIK_OTHER_ERROR_CODE = '111120';

    public const MAX_POLLING_ATTEMPTS = 10;
    public const POLLING_INTERVAL_SECONDS = 3;
    public const POLLING_TIMEOUT_SECONDS = 30;
}

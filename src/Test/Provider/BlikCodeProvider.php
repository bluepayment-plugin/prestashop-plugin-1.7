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

namespace BluePayment\Test\Provider;

use BluePayment\Test\Config\Data\TestDataConfig;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for BLIK codes used in testing
 */
final class BlikCodeProvider
{
    /**
     * Get BLIK code based on test mode
     *
     * @param string $testCase Test case to use (success, invalid, expired, used, other_error)
     *
     * @return string BLIK code
     */
    public function getBlikCode(string $testCase = 'success'): string
    {
        // If in sandbox mode, return test BLIK code
        if (\Configuration::get('BLUEPAYMENT_TEST_ENV')) {
            switch ($testCase) {
                case 'invalid':
                    return TestDataConfig::BLIK_INVALID_CODE;
                case 'expired':
                    return TestDataConfig::BLIK_EXPIRED_CODE;
                case 'used':
                    return TestDataConfig::BLIK_USED_CODE;
                case 'other_error':
                    return TestDataConfig::BLIK_OTHER_ERROR_CODE;
                case 'success':
                default:
                    return TestDataConfig::BLIK_SUCCESS_CODE;
            }
        }

        // In production mode, return random 6-digit code (for testing purposes only)
        return (string) mt_rand(100000, 999999);
    }
}

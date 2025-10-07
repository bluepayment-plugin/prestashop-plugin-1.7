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
use Configuration;
use Customer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for test customer data
 */
class CustomerProvider
{
    /**
     * Get test customer if exists
     *
     * @return \Customer|null Customer object or null if not found
     */
    public function getTestCustomer(): ?\Customer
    {
        $customerId = (int) \Configuration::get(TestDataConfig::TEST_CUSTOMER_ID_KEY);

        if ($customerId) {
            $customer = new \Customer($customerId);

            if (\Validate::isLoadedObject($customer)) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Save test customer ID to configuration
     *
     * @param int $customerId Customer ID to save
     *
     * @return bool Success status
     */
    public function saveTestCustomerId(int $customerId): bool
    {
        return \Configuration::updateValue(TestDataConfig::TEST_CUSTOMER_ID_KEY, $customerId);
    }

    /**
     * Prepare data for new test customer
     *
     * @return array Customer data
     */
    public function prepareTestCustomerData(): array
    {
        return [
            'firstname' => 'Test',
            'lastname' => 'BLIK',
            'email' => 'test.blik.' . time() . '@test.com',
            'passwd' => \Tools::encrypt('test123'),
            'is_guest' => 0,
            'active' => 1,
        ];
    }
}

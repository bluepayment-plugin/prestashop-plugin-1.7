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

use Address;
use BluePayment\Test\Config\Data\TestDataConfig;
use Configuration;
use Customer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for test address data
 */
class AddressProvider
{
    /**
     * Get test address if exists
     *
     * @return \Address|null Address object or null if not found
     */
    public function getTestAddress(): ?\Address
    {
        $addressId = (int) \Configuration::get(TestDataConfig::TEST_ADDRESS_ID_KEY);

        if ($addressId) {
            $address = new \Address($addressId);

            if (\Validate::isLoadedObject($address)) {
                return $address;
            }
        }

        return null;
    }

    /**
     * Save test address ID to configuration
     *
     * @param int $addressId Address ID to save
     *
     * @return bool Success status
     */
    public function saveTestAddressId(int $addressId): bool
    {
        return \Configuration::updateValue(TestDataConfig::TEST_ADDRESS_ID_KEY, $addressId);
    }

    /**
     * Prepare data for new test address
     *
     * @param \Customer $customer Customer to associate address with
     *
     * @return array Address data
     */
    public function prepareTestAddressData(\Customer $customer): array
    {
        $defaultCountryId = (int) \Configuration::get('PS_COUNTRY_DEFAULT');

        return [
            'id_customer' => $customer->id,
            'id_country' => $defaultCountryId,
            'alias' => 'Test BLIK Address',
            'lastname' => $customer->lastname,
            'firstname' => $customer->firstname,
            'address1' => 'Test Street 123',
            'city' => 'Test City',
            'postcode' => '00-000',
            'phone' => '123456789',
        ];
    }
}

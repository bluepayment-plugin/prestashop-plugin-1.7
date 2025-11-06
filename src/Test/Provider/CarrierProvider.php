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

use BluePayment\Test\Exception\TestException;
use Carrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Provider for test carrier data
 */
class CarrierProvider
{
    /**
     * Get default carrier
     *
     * @return \Carrier Default carrier
     *
     * @throws TestException When no valid carrier is found
     */
    public function getDefaultCarrier(): \Carrier
    {
        $carrierId = (int) \Configuration::get('PS_CARRIER_DEFAULT');
        if ($carrierId) {
            $carrier = new \Carrier($carrierId);
            if (\Validate::isLoadedObject($carrier) && $carrier->active) {
                return $carrier;
            }
        }

        // Try to get any active carrier
        $carriers = \Carrier::getCarriers(
            \Context::getContext()->language->id,
            true,
            false,
            false,
            null,
            \Carrier::ALL_CARRIERS
        );

        if (!empty($carriers)) {
            $carrier = new \Carrier((int) $carriers[0]['id_carrier']);
            if (\Validate::isLoadedObject($carrier)) {
                return $carrier;
            }
        }

        throw new TestException('No valid carrier found for testing', ['carriers_checked' => count($carriers)]);
    }
}

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

namespace BluePayment\Until;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EmployeeAccess
{
    public const PERMISSION_TAB = 'AdminBluepaymentPayments';

    public static function canEditPayments($employee): bool
    {
        if (null === $employee || !\Validate::isLoadedObject($employee)) {
            return false;
        }

        $idTab = (int) \Tab::getIdFromClassName(self::PERMISSION_TAB);

        if (0 === $idTab) {
            \PrestaShopLogger::addLog('Autopay - permission tab ' . self::PERMISSION_TAB . ' not found', 2);

            return false;
        }

        $access = \Profile::getProfileAccess((int) $employee->id_profile, $idTab);

        if (!is_array($access)) {
            return false;
        }

        return !empty($access['edit']) && '1' === (string) $access['edit'];
    }
}

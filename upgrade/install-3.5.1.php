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
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_5_1($module)
{
    $removedFields = [
        $module->name_upper . '_GA_TYPE',
        $module->name_upper . '_GA_TRACKER_ID',
    ];

    foreach ($removedFields as $field) {
        if (!Configuration::deleteByName($field)) {
            PrestaShopLogger::addLog(
                'BluepaymentUpgrade 3.5.1: Failed to delete configuration ' . $field,
                3
            );

            return false;
        }
    }

    PrestaShopLogger::addLog(
        'BluepaymentUpgrade 3.5.1: Removed Universal Analytics configuration, GA4 is now the only option',
        1
    );

    return true;
}

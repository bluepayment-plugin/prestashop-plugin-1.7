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

/**
 * Replace deprecated hook aliases with canonical hook names
 */
function upgrade_module_3_6_0($module)
{
    $hooks = [
        'paymentReturn' => 'displayPaymentReturn',
        'orderConfirmation' => 'displayOrderConfirmation',
    ];

    foreach ($hooks as $alias => $hookName) {
        if (!$module->registerHook($hookName)) {
            PrestaShopLogger::addLog('BluepaymentUpgrade 3.6.0: Failed to register hook ' . $hookName, 3);

            return false;
        }

        $module->unregisterHook($alias);
    }

    return true;
}

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

namespace BluePayment\Test\Executor;

use BluePayment\Test\Config\Test\TransactionTestConfig;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class TransactionExecutor extends AbstractTestExecutor
{
    protected function getSpecificCheckerClasses(): array
    {
        return TransactionTestConfig::getOrderedCheckerClasses();
    }
}

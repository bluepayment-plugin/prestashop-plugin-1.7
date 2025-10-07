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

namespace BluePayment\Test\Config\Test;

use BluePayment\Test\Checker\Transaction\BlikChannelChecker;
use BluePayment\Test\Checker\Transaction\BlikTransactionChecker;
use BluePayment\Test\Checker\Transaction\ITNCheckChecker;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class TransactionTestConfig
{
    public static function getOrderedCheckerClasses(): array
    {
        return [
            ITNCheckChecker::class,
            //            BlikChannelChecker::class,
            //            BlikTransactionChecker::class,
        ];
    }
}

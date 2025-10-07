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

use BluePayment\Test\Checker\Connection\ApiConnectionChecker;
use BluePayment\Test\Checker\Connection\GatewayChannelsConnectionChecker;
use BluePayment\Test\Checker\Connection\GatewayTransfersConnectionChecker;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class ConnectionTestConfig
{
    /**
     * Zwraca uporządkowaną listę klas checkerów
     *
     * @return array
     */
    public static function getOrderedCheckerClasses(): array
    {
        return [
            ApiConnectionChecker::class,
            GatewayChannelsConnectionChecker::class,
            GatewayTransfersConnectionChecker::class,
        ];
    }
}

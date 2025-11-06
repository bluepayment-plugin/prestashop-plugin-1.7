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

namespace BluePayment\Test\Checker\Common;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for HTTPS protocol
 */
final class HttpsChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function check(): array
    {
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        if (!$isHttps) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('HTTPS protocol is not enabled. Payment gateway requires secure connection'),
                'details' => [
                    'https_enabled' => false,
                    'current_protocol' => 'http',
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('HTTPS protocol is enabled'),
            'details' => [
                'https_enabled' => true,
                'current_protocol' => 'https',
            ],
        ];
    }

    public function getName(): string
    {
        return $this->module->l('HTTPS Protocol Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the website uses HTTPS protocol');
    }
}

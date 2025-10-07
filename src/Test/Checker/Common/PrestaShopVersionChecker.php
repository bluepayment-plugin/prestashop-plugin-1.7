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
 * Checker for PrestaShop version compatibility
 */
final class PrestaShopVersionChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * Minimum supported PrestaShop version
     *
     * @var string
     */
    private $minVersion = '1.7.3.0';

    /**
     * Maximum supported PrestaShop version
     *
     * @var string
     */
    private $maxVersion = _PS_VERSION_;

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function check(): array
    {
        $currentVersion = _PS_VERSION_;

        // Check if current version is below minimum supported version
        if (version_compare($currentVersion, $this->minVersion, '<')) {
            return [
                'status' => 'error',
                'message' => sprintf(
                    $this->module->l('PrestaShop version %s is not supported. Minimum required version is %s'),
                    $currentVersion,
                    $this->minVersion
                ),
                'details' => [
                    'current_version' => $currentVersion,
                    'min_version' => $this->minVersion,
                    'max_version' => $this->maxVersion,
                    'is_supported' => false,
                ],
            ];
        }

        if (version_compare($currentVersion, $this->maxVersion, '>')) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('PrestaShop version %s might not be fully supported. Maximum tested version is %s'),
                    $currentVersion,
                    $this->maxVersion
                ),
                'details' => [
                    'current_version' => $currentVersion,
                    'min_version' => $this->minVersion,
                    'max_version' => $this->maxVersion,
                    'is_supported' => false,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf(
                $this->module->l('PrestaShop version %s is supported'),
                $currentVersion
            ),
            'details' => [
                'current_version' => $currentVersion,
                'min_version' => $this->minVersion,
                'max_version' => $this->maxVersion,
                'is_supported' => true,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->module->l('PrestaShop Version Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the PrestaShop version is compatible with the module');
    }
}

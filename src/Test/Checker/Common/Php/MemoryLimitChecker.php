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

namespace BluePayment\Test\Checker\Common\Php;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Utility\Converter\MemoryUnitConverter;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for PHP memory limit
 */
final class MemoryLimitChecker implements CheckerInterface
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
     * Minimum recommended memory limit in MB
     *
     * @var int
     */
    private $minMemoryLimit = 128;

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
        $memoryLimit = $this->getMemoryLimitInMB();

        // If memory_limit is set to -1 (unlimited), it's always sufficient
        if ($memoryLimit === -1) {
            return [
                'status' => 'success',
                'message' => $this->module->l('PHP memory limit is set to unlimited'),
                'details' => [
                    'memory_limit' => MemoryUnitConverter::formatMB(-1),
                    'min_required' => $this->minMemoryLimit . 'MB',
                    'is_sufficient' => true,
                ],
            ];
        }

        if ($memoryLimit < $this->minMemoryLimit) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('PHP memory limit (%dMB) is below the recommended minimum (%dMB)'),
                    $memoryLimit,
                    $this->minMemoryLimit
                ),
                'details' => [
                    'memory_limit' => MemoryUnitConverter::formatMB($memoryLimit),
                    'min_required' => $this->minMemoryLimit . 'MB',
                    'is_sufficient' => false,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf(
                $this->module->l('PHP memory limit (%dMB) is sufficient'),
                $memoryLimit
            ),
            'details' => [
                'memory_limit' => MemoryUnitConverter::formatMB($memoryLimit),
                'min_required' => $this->minMemoryLimit . 'MB',
                'is_sufficient' => true,
            ],
        ];
    }

    /**
     * Get the PHP memory limit in MB
     *
     * @return int Memory limit in MB, or -1 if unlimited
     */
    private function getMemoryLimitInMB(): int
    {
        $memoryLimit = ini_get('memory_limit');

        return MemoryUnitConverter::convertToMB($memoryLimit);
    }

    public function getName(): string
    {
        return $this->module->l('PHP Memory Limit Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if PHP memory limit is sufficient for payment processing');
    }
}

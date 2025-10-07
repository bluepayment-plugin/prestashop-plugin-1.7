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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for PHP execution time limit
 */
final class TimeoutChecker implements CheckerInterface
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
     * Minimum recommended execution time limit in seconds
     *
     * @var int
     */
    private $minTimeLimit = 30;

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
        $timeLimit = $this->getMaxExecutionTime();

        if ($timeLimit === 0) {
            return [
                'status' => 'success',
                'message' => $this->module->l('PHP execution time limit is set to unlimited'),
                'details' => [
                    'time_limit' => 'unlimited (0s)',
                    'min_required' => $this->minTimeLimit . 's',
                    'is_sufficient' => true,
                ],
            ];
        }

        if ($timeLimit < $this->minTimeLimit) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('PHP execution time limit (%ds) is below the recommended minimum (%ds)'),
                    $timeLimit,
                    $this->minTimeLimit
                ),
                'details' => [
                    'time_limit' => $timeLimit . 's',
                    'min_required' => $this->minTimeLimit . 's',
                    'is_sufficient' => false,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf(
                $this->module->l('PHP execution time limit (%ds) is sufficient'),
                $timeLimit
            ),
            'details' => [
                'time_limit' => $timeLimit . 's',
                'min_required' => $this->minTimeLimit . 's',
                'is_sufficient' => true,
            ],
        ];
    }

    /**
     * Get the PHP max execution time in seconds
     *
     * @return int Max execution time in seconds, or 0 if unlimited
     */
    private function getMaxExecutionTime(): int
    {
        $timeLimit = ini_get('max_execution_time');

        if ($timeLimit === false || $timeLimit === '') {
            return 0;
        }

        return (int) $timeLimit;
    }

    public function getName(): string
    {
        return $this->module->l('PHP Execution Time Limit Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if PHP execution time limit is sufficient for payment processing');
    }
}

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

namespace BluePayment\Test\Executor\Interfaces;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BluePayment\Test\Exception\TestException;
use BluePayment\Test\Logger\Interfaces\TestLoggerInterface;

interface TestExecutorInterface
{
    /**
     * @param string $step
     *
     * @return array
     *
     * @throws TestException
     */
    public function execute(string $step): array;

    /**
     * @return array
     */
    public function getAvailableTestSteps(): array;

    public function startNewTestSession(): void;

    /**
     * @param string $stepName
     * @param int $stepNumber
     */
    public function logTestStep(string $stepName, int $stepNumber): void;

    /**
     * @param string $status
     * @param array $statistics
     */
    public function logTestSummary(string $status, array $statistics = []): void;

    /**
     * @return TestLoggerInterface
     */
    public function getLogger(): TestLoggerInterface;
}

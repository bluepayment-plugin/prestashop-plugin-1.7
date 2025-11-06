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

namespace BluePayment\Test\Factory;

use BluePayment\Test\Config\Logger\TestLoggerConfig;
use BluePayment\Test\Executor\ConnectionExecutor;
use BluePayment\Test\Executor\Interfaces\TestExecutorInterface;
use BluePayment\Test\Executor\TransactionExecutor;
use BluePayment\Test\Logger\FileTestLogger;
use BluePayment\Test\Logger\Interfaces\TestLoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class TestExecutorFactory
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
     * @var TestLoggerConfig|null
     */
    private $loggerConfig;

    /**
     * @param \Module $module
     * @param \Context $context
     * @param TestLoggerConfig|null $loggerConfig
     */
    public function __construct(\Module $module, \Context $context, TestLoggerConfig $loggerConfig = null)
    {
        $this->module = $module;
        $this->context = $context;
        $this->loggerConfig = is_null($loggerConfig) ? new TestLoggerConfig() : $loggerConfig;
    }

    /**
     * Creates a test executor for the specified test type
     *
     * @param string $testType Test type (connection, transaction)
     *
     * @return TestExecutorInterface
     *
     * @throws \Exception
     */
    public function createTestExecutor(string $testType): TestExecutorInterface
    {
        $logger = $this->createLogger($testType);

        switch ($testType) {
            case 'connection':
                return new ConnectionExecutor($this->module, $this->context, $logger);
            case 'transaction':
                return new TransactionExecutor($this->module, $this->context, $logger);
            default:
                throw new \Exception(sprintf('Unknown test type: %s', $testType));
        }
    }

    /**
     * Creates a logger for the specified test type
     *
     * @param string $testType Test type (connection, transaction)
     *
     * @return TestLoggerInterface
     */
    public function createLogger(string $testType): TestLoggerInterface
    {
        return new FileTestLogger($testType, $this->loggerConfig);
    }
}

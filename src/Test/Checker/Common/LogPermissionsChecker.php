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
use BluePayment\Test\Config\Logger\TestLoggerConfig;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for log directory permissions
 */
final class LogPermissionsChecker implements CheckerInterface
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
     * @var string
     */
    private $logDir;

    /**
     * @var TestLoggerConfig
     */
    private $loggerConfig;

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
        $this->loggerConfig = new TestLoggerConfig();
        $this->logDir = $this->loggerConfig->getLogDirectory();
    }

    public function check(): array
    {
        if (!is_dir($this->logDir)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Log directory does not exist'),
                'details' => [
                    'log_dir' => $this->logDir,
                    'exists' => false,
                ],
            ];
        }

        if (!is_writable($this->logDir)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Log directory is not writable. Unable to save logs'),
                'details' => [
                    'log_dir' => $this->logDir,
                    'exists' => true,
                    'writable' => false,
                ],
            ];
        }

        // Test if we can actually write a file
        $testFile = $this->logDir . 'autopay_test_' . time() . '.tmp';
        $writeTest = @file_put_contents($testFile, 'Test');

        if ($writeTest === false) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Cannot write to log directory. Check file permissions'),
                'details' => [
                    'log_dir' => $this->logDir,
                    'exists' => true,
                    'writable' => true,
                    'write_test' => false,
                ],
            ];
        }

        // Clean up test file
        @unlink($testFile);

        return [
            'status' => 'success',
            'message' => $this->module->l('Log directory is writable'),
            'details' => [
                'log_dir' => $this->logDir,
                'exists' => true,
                'writable' => true,
                'write_test' => true,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->module->l('Log Permissions Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the log directory has proper write permissions');
    }
}

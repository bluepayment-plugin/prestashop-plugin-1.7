<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Test\Error\Handler;

use BluePayment\Test\Config\Logger\TestLoggerConfig;
use BluePayment\Test\Logger\FileTestLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class for handling PHP errors and exceptions
 */
final class ErrorHandler
{
    /**
     * @var FileTestLogger
     */
    private $logger;

    /**
     * @var bool
     */
    private $isRegistered = false;

    /**
     * @param string $testType Typ testu (connection, transaction)
     */
    public function __construct(string $testType = 'connection')
    {
        $config = new TestLoggerConfig();

        $this->logger = new FileTestLogger($testType, $config);
    }

    public function register(): void
    {
        if ($this->isRegistered) {
            return;
        }

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleFatalError']);

        $this->isRegistered = true;
        $this->logger->info('Error handler registered');
    }

    public function unregister(): void
    {
        if (!$this->isRegistered) {
            return;
        }

        restore_error_handler();
        restore_exception_handler();

        $this->isRegistered = false;
        $this->logger->info('Error handler unregistered');
    }

    /**
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile File where the error occurred
     * @param int $errline Line number where the error occurred
     *
     * @return bool
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (error_reporting() === 0) {
            return false;
        }

        $errorType = $this->getErrorType($errno);

        $this->logger->error(
            sprintf('%s: %s in %s on line %d', $errorType, $errstr, $errfile, $errline),
            [
                'error_type' => $errorType,
                'error_code' => $errno,
                'file' => $errfile,
                'line' => $errline,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            ]
        );

        return true;
    }

    /**
     * @param \Throwable $exception
     */
    public function handleException(\Throwable $exception): void
    {
        $this->logger->error(
            sprintf('Uncaught exception: %s', $exception->getMessage()),
            [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ]
        );
    }

    /**
     * @param \Throwable $exception Exception to log
     */
    public function logException(\Throwable $exception): void
    {
        $this->logger->error(
            sprintf('Exception: %s', $exception->getMessage()),
            [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ]
        );
    }

    public function handleFatalError(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->error(
                sprintf('Fatal error: %s in %s on line %d', $error['message'], $error['file'], $error['line']),
                [
                    'error_type' => 'Fatal Error',
                    'error_code' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]
            );
        }
    }

    /**
     * @param int $errno Error number
     *
     * @return string
     */
    private function getErrorType(int $errno): string
    {
        switch ($errno) {
            case E_ERROR:
                return 'Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }
}

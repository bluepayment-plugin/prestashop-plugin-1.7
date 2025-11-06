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

namespace BluePayment\Test\Logger;

use BluePayment\Test\Config\Logger\TestLoggerConfig;
use BluePayment\Test\Logger\Interfaces\TestLoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * File-based implementation of test logger
 */
final class FileTestLogger implements TestLoggerInterface
{
    /**
     * @var string
     */
    private $logFilePath;

    /**
     * @var string
     */
    private $testType;

    /**
     * @var TestLoggerConfig
     */
    private $config;

    /**
     * @param string $testType Test type (connection, transaction)
     * @param TestLoggerConfig|null $config Logger configuration
     */
    public function __construct(string $testType, TestLoggerConfig $config = null)
    {
        $this->testType = $testType;
        $this->config = $config ?? new TestLoggerConfig();
        $this->logFilePath = $this->initLogFile();
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $this->checkRotation();

        $timestamp = date($this->config->getDateFormat());
        $contextString = '';

        if (!empty($context)) {
            $contextString = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $contextString = '    ' . str_replace("\n", "\n    ", $contextString);

            if (substr($contextString, -5) === '    }') {
                $contextString = substr($contextString, 0, -5) . '}';
            }
        }

        $formattedMessage = sprintf(
            $this->config->getLogEntryFormat(),
            $timestamp,
            strtoupper($level),
            $this->testType,
            $message,
            $contextString
        );

        file_put_contents($this->logFilePath, $formattedMessage, FILE_APPEND);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    /**
     * Initializes the log file
     *
     * @return string Path to the log file
     */
    private function initLogFile(): string
    {
        $logDir = $this->config->getLogDirectory();

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
            throw new \RuntimeException(sprintf('Cannot create logs directory: %s', $logDir));
        }

        $logFile = $this->config->getLogFilePath($this->testType);

        if (!file_exists($logFile)) {
            $this->createNewLogFile($logFile);
        }

        return $logFile;
    }

    /**
     * Creates a new log file
     *
     * @param string $logFile Path to the log file
     */
    private function createNewLogFile(string $logFile): void
    {
        $header = sprintf(
            $this->config->getLogEntryFormat(),
            date($this->config->getDateFormat()),
            'INFO',
            $this->testType,
            '=== New log file started ===',
            ''
        );

        file_put_contents($logFile, $header);
        chmod($logFile, 0644);
    }

    /**
     * Rozpoczyna nową sesję testową z wyraźnym oznaczeniem w logu
     */
    public function startNewTestSession(): void
    {
        $separator = str_repeat('=', 50);
        $this->info($separator);
        $this->info('=== New test session started ===');
        $this->info($separator);
    }

    /**
     * Loguje informację o etapie testu
     *
     * @param string $stepName Nazwa etapu testu
     * @param int $stepNumber Numer etapu testu
     */
    public function logTestStep(string $stepName, int $stepNumber): void
    {
        $separator = str_repeat('-', 30);
        $this->info($separator);
        $this->info(sprintf('--- Step %d: %s ---', $stepNumber, $stepName));
        $this->info($separator);
    }

    /**
     * Loguje podsumowanie testu
     *
     * @param string $status Status testu (SUCCESS/FAILURE)
     * @param array $statistics Statystyki testu
     */
    public function logTestSummary(string $status, array $statistics = []): void
    {
        $separator = str_repeat('=', 50);
        $this->info($separator);
        $this->info(sprintf('=== Test completed: %s ===', $status), $statistics);
        $this->info($separator);
    }

    /**
     * Checks if the log file needs rotation
     */
    private function checkRotation(): void
    {
        if (!file_exists($this->logFilePath)) {
            $this->createNewLogFile($this->logFilePath);

            return;
        }

        $fileSize = filesize($this->logFilePath);

        if ($fileSize > $this->config->getMaxFileSize()) {
            $this->rotateLogFile();
        }
    }

    /**
     * Rotates the log file
     */
    private function rotateLogFile(): void
    {
        $timestamp = date('Ymd_His');
        $archiveFile = sprintf(
            $this->config->getArchiveFileNamePattern($this->testType),
            $timestamp
        );

        rename($this->logFilePath, $archiveFile);
        $this->createNewLogFile($this->logFilePath);

        // Clean up old log files
        $this->cleanOldLogFiles();
    }

    /**
     * Removes log files older than the configured maximum age
     */
    private function cleanOldLogFiles(): void
    {
        $maxAge = $this->config->getMaxFileAge();
        $now = time();

        // Find all archived log files for this test type
        $pattern = $this->config->getLogDirectory() . $this->testType . '_test*.log';
        $files = glob($pattern);

        foreach ($files as $file) {
            // Skip the current log file
            if ($file === $this->logFilePath) {
                continue;
            }

            $fileAge = $now - filemtime($file);
            if ($fileAge > $maxAge) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}

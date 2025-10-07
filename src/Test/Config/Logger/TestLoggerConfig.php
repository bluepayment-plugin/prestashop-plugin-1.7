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

namespace BluePayment\Test\Config\Logger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Configuration class for test logger
 */
final class TestLoggerConfig
{
    /**
     * @var string
     */
    private $logDirectory;

    /**
     * @var string
     */
    private $logFileNamePattern;

    /**
     * @var string
     */
    private $archiveFileNamePattern;

    /**
     * @var int
     */
    private $maxFileSize;

    /**
     * @var int
     */
    private $maxFileAge;

    /**
     * @var string
     */
    private $dateFormat;

    /**
     * @var string
     */
    private $logEntryFormat;

    public function __construct()
    {
        $this->logDirectory = _PS_MODULE_DIR_ . 'bluepayment/logs/';
        $this->logFileNamePattern = '%s_test.log';
        $this->archiveFileNamePattern = '%s_test_%s.log';
        $this->maxFileSize = 5 * 1024 * 1024; // 5 MB
        $this->maxFileAge = 7 * 24 * 60 * 60; // 7 days
        $this->dateFormat = 'Y-m-d H:i:s';
        $this->logEntryFormat = "[%s] [%s] [%s] %s\n%s\n";
    }

    /**
     * Gets the log directory
     *
     * @return string
     */
    public function getLogDirectory(): string
    {
        return $this->logDirectory;
    }

    /**
     * Gets the log file path for the specified test type
     *
     * @param string $testType Test type
     *
     * @return string
     */
    public function getLogFilePath(string $testType): string
    {
        return $this->logDirectory . sprintf($this->logFileNamePattern, $testType);
    }

    /**
     * Gets the archive file name pattern
     *
     * @param string $testType Test type
     *
     * @return string
     */
    public function getArchiveFileNamePattern(string $testType): string
    {
        return $this->logDirectory . sprintf($this->archiveFileNamePattern, $testType, '%s');
    }

    /**
     * Gets the maximum file size in bytes
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Gets the maximum file age in seconds
     *
     * @return int
     */
    public function getMaxFileAge(): int
    {
        return $this->maxFileAge;
    }

    /**
     * Gets the date format
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Gets the log entry format
     *
     * @return string
     */
    public function getLogEntryFormat(): string
    {
        return $this->logEntryFormat;
    }
}

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
final class LogFileManager
{
    /**
     * @var TestLoggerConfig
     */
    private $config;

    /**
     * @param TestLoggerConfig|null $config
     */
    public function __construct(?TestLoggerConfig $config = null)
    {
        $this->config = $config ?? new TestLoggerConfig();
    }

    /**
     * @param TestLoggerInterface $logger
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getLogFilePath(TestLoggerInterface $logger): string
    {
        return $this->resolveLogFilePath($logger->getLogFilePath());
    }

    /**
     * @param string $logFilePath
     * @param string $testType
     *
     * @throws \Exception
     */
    public function downloadLogFile(string $logFilePath, string $testType): void
    {
        $logFilePath = $this->resolveLogFilePath($logFilePath);
        $fileName = 'test_' . $this->sanitizeTestType($testType) . '_' . date('Y-m-d_H-i-s') . '.log';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($logFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($logFilePath);
        exit;
    }

    /**
     * @param string $logFilePath
     *
     * @return string
     *
     * @throws \Exception
     */
    private function resolveLogFilePath(string $logFilePath): string
    {
        $logDirectory = realpath($this->config->getLogDirectory());
        $realPath = realpath($logFilePath);

        if ($logDirectory === false || $realPath === false || !is_file($realPath)) {
            throw new \Exception('Log file does not exist');
        }

        $logDirectory = rtrim($logDirectory, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;

        if (strpos($realPath, $logDirectory) !== 0
            || strpos(substr($realPath, strlen($logDirectory)), \DIRECTORY_SEPARATOR) !== false
            || strtolower((string) pathinfo($realPath, \PATHINFO_EXTENSION)) !== 'log'
        ) {
            throw new \Exception('Log file does not exist');
        }

        return $realPath;
    }

    /**
     * @param string $testType
     *
     * @return string
     */
    private function sanitizeTestType(string $testType): string
    {
        return (string) preg_replace('/[^a-zA-Z0-9_-]/', '', $testType);
    }
}

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

use BluePayment\Test\Logger\Interfaces\TestLoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}
final class LogFileManager
{
    /**
     * @param TestLoggerInterface $logger
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getLogFilePath(TestLoggerInterface $logger): string
    {
        $logFilePath = $logger->getLogFilePath();

        if (!file_exists($logFilePath)) {
            throw new \Exception('Log file does not exist');
        }

        return $logFilePath;
    }

    /**
     * @param string $logFilePath
     * @param string $testType
     */
    public function downloadLogFile(string $logFilePath, string $testType): void
    {
        $fileName = 'test_' . $testType . '_' . date('Y-m-d_H-i-s') . '.log';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($logFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($logFilePath);
        exit;
    }
}

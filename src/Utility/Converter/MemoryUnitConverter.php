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

namespace BluePayment\Utility\Converter;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Utility class for memory unit conversion
 */
final class MemoryUnitConverter
{
    /**
     * Convert a memory limit string (like '128M', '1G') to megabytes
     *
     * @param string $memoryLimit Memory limit string from php.ini
     *
     * @return int Memory limit in MB, or -1 if unlimited
     */
    public static function convertToMB(string $memoryLimit): int
    {
        // If no memory limit is set, return -1 (unlimited)
        if ($memoryLimit === '' || $memoryLimit === false) {
            return -1;
        }

        // If memory limit is set to -1, it means unlimited
        if ($memoryLimit === '-1') {
            return -1;
        }

        // Extract the numeric value and unit
        if (!preg_match('/^(\d+)([KMG]?)$/', $memoryLimit, $matches)) {
            return -1; // Invalid format, return unlimited
        }

        $value = (int) $matches[1];
        $unit = $matches[2];

        // Convert to MB based on the unit
        switch ($unit) {
            case 'K':
                return (int) ($value / 1024);
            case 'M':
                return $value;
            case 'G':
                return $value * 1024;
            default:
                return (int) ($value / 1048576); // Convert bytes to MB
        }
    }

    /**
     * Format a memory size in bytes to a human-readable string
     *
     * @param int $bytes Memory size in bytes
     * @param int $precision Number of decimal places
     *
     * @return string Formatted memory size
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format memory limit in MB to a human-readable string
     *
     * @param int $mb Memory size in megabytes
     *
     * @return string Formatted memory size
     */
    public static function formatMB(int $mb): string
    {
        if ($mb === -1) {
            return 'Unlimited';
        }

        return $mb . 'MB';
    }
}

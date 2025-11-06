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

use BluePayment\Test\Checker\Common\Php\ExtensionCurlChecker;
use BluePayment\Test\Checker\Common\Php\ExtensionOpenSSLChecker;
use BluePayment\Test\Checker\Common\Php\ExtensionXmlChecker;
use BluePayment\Test\Checker\Common\Php\ExtensionZipChecker;
use BluePayment\Test\Checker\Common\Php\MemoryLimitChecker;
use BluePayment\Test\Checker\Common\Php\TimeoutChecker;
use BluePayment\Test\Checker\Common\Php\VersionChecker;
use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Composite checker for PHP environment
 * Uses the Composite pattern to combine multiple PHP-related checkers
 */
final class PhpEnvironmentChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var CheckerInterface[]
     */
    private $checkers = [];

    public function __construct(\Module $module)
    {
        $this->module = $module;

        // PHP version and extensions checks
        $this->checkers[] = new VersionChecker($module);
        $this->checkers[] = new ExtensionXmlChecker($module);
        $this->checkers[] = new ExtensionCurlChecker($module);
        $this->checkers[] = new ExtensionZipChecker($module);
        $this->checkers[] = new ExtensionOpenSSLChecker($module);

        // PHP limits checks
        $this->checkers[] = new MemoryLimitChecker($module);
        $this->checkers[] = new TimeoutChecker($module);
    }

    public function check(): array
    {
        $results = [];
        $overallStatus = 'success';
        $errorMessages = [];
        $warningMessages = [];

        foreach ($this->checkers as $checker) {
            $result = $checker->check();
            $results[] = $result;

            if ($result['status'] === 'error') {
                $overallStatus = 'error';
                $errorMessages[] = $result['message'];
            } elseif ($result['status'] === 'warning' && $overallStatus !== 'error') {
                $overallStatus = 'warning';
                $warningMessages[] = $result['message'];
            }
        }

        $message = $this->getSummaryMessage($overallStatus, $errorMessages, $warningMessages);

        return [
            'status' => $overallStatus,
            'message' => $message,
            'details' => [
                'component_results' => $results,
                'error_count' => count($errorMessages),
                'warning_count' => count($warningMessages),
            ],
        ];
    }

    /**
     * Generate a summary message based on results
     */
    private function getSummaryMessage(string $status, array $errorMessages, array $warningMessages): string
    {
        switch ($status) {
            case 'error':
                return $this->module->l('PHP environment check failed') . ': ' . implode('; ', $errorMessages);
            case 'warning':
                return $this->module->l('PHP environment check completed with warnings') . ': ' . implode('; ', $warningMessages);
            default:
                return $this->module->l('PHP environment check passed successfully');
        }
    }

    public function getName(): string
    {
        return $this->module->l('PHP Environment Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks PHP version, required extensions (XML, CURL, ZIP, OpenSSL), memory limit and execution time limit');
    }
}

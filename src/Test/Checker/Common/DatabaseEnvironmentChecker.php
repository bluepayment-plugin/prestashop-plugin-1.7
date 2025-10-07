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

use BluePayment\Test\Checker\Common\Database\ConnectionAvailabilityChecker;
use BluePayment\Test\Checker\Common\Database\InnoDBChecker;
use BluePayment\Test\Checker\Common\Database\ReadPermissionChecker;
use BluePayment\Test\Checker\Common\Database\WritePermissionChecker;
use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Composite checker for database environment
 * Uses the Composite pattern to combine multiple database-related checkers
 */
final class DatabaseEnvironmentChecker implements CheckerInterface
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
     * @var CheckerInterface[]
     */
    private $checkers = [];

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;

        // Dodajemy poszczególne checkery
        $this->checkers[] = new ConnectionAvailabilityChecker($module, $context);
        $this->checkers[] = new ReadPermissionChecker($module, $context);
        $this->checkers[] = new WritePermissionChecker($module, $context);
        $this->checkers[] = new InnoDBChecker($module, $context);
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
     * @param string $status
     * @param array $errorMessages
     * @param array $warningMessages
     *
     * @return string
     */
    private function getSummaryMessage(string $status, array $errorMessages, array $warningMessages): string
    {
        switch ($status) {
            case 'error':
                return $this->module->l('Database environment check failed') . ': ' . implode('; ', $errorMessages);
            case 'warning':
                return $this->module->l('Database environment check completed with warnings') . ': ' . implode('; ', $warningMessages);
            default:
                return $this->module->l('Database environment check passed successfully');
        }
    }

    public function getName(): string
    {
        return $this->module->l('Database Environment Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks database connection, read/write permissions and InnoDB engine availability');
    }
}

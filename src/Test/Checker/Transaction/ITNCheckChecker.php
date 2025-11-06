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

namespace BluePayment\Test\Checker\Transaction;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Test\Checker\Transaction\ITN\FriendlyUrlChecker;
use BluePayment\Test\Checker\Transaction\ITN\ITNReturnUrlChecker;
use BluePayment\Test\Checker\Transaction\ITN\ITNStatusUrlChecker;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Composite checker for ITN functionality
 */
class ITNCheckChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

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

        $this->checkers[] = new FriendlyUrlChecker($module);
        $this->checkers[] = new ITNReturnUrlChecker($module, $context);
        $this->checkers[] = new ITNStatusUrlChecker($module, $context);
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
                'checkers_count' => count($this->checkers),
            ],
        ];
    }

    public function getName(): string
    {
        return 'ITN Check';
    }

    public function getDescription(): string
    {
        return 'Tests if friendly URLs are enabled and payment notification endpoints are accessible';
    }

    /**
     * Generuje podsumowanie na podstawie statusu i komunikatów
     *
     * @param string $status Status ogólny
     * @param array $errorMessages Lista komunikatów o błędach
     * @param array $warningMessages Lista ostrzeżeń
     *
     * @return string
     */
    private function getSummaryMessage(string $status, array $errorMessages, array $warningMessages): string
    {
        if ($status === 'error') {
            return $this->module->l('ITN check failed') . ': ' . implode(', ', $errorMessages);
        }

        if ($status === 'warning') {
            return $this->module->l('ITN check completed with warnings') . ': ' . implode(', ', $warningMessages);
        }

        return $this->module->l('All ITN checks passed successfully');
    }
}

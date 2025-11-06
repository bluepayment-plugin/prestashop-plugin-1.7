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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for internet connection
 */
final class InternetConnectionChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var array
     */
    private $testHosts = [
        'autopay.eu',
        'google.com',
        'cloudflare.com',
    ];

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): array
    {
        $connectedHosts = [];
        $failedHosts = [];

        foreach ($this->testHosts as $host) {
            $connected = $this->checkHostConnection($host);

            if ($connected) {
                $connectedHosts[] = $host;
            } else {
                $failedHosts[] = $host;
            }
        }

        if (empty($connectedHosts)) {
            return [
                'status' => 'error',
                'message' => $this->module->l('No internet connection detected'),
                'details' => [
                    'connected' => false,
                    'tested_hosts' => $this->testHosts,
                    'failed_hosts' => $failedHosts,
                ],
            ];
        }

        if (!empty($failedHosts)) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('Internet connection detected but some hosts are unreachable'),
                'details' => [
                    'connected' => true,
                    'tested_hosts' => $this->testHosts,
                    'connected_hosts' => $connectedHosts,
                    'failed_hosts' => $failedHosts,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('Internet connection is working properly'),
            'details' => [
                'connected' => true,
                'tested_hosts' => $this->testHosts,
                'connected_hosts' => $connectedHosts,
            ],
        ];
    }

    /**
     * Check connection to a specific host
     *
     * @param string $host
     *
     * @return bool
     */
    private function checkHostConnection(string $host): bool
    {
        $errno = 0;
        $errstr = '';
        $timeout = 5;

        $connection = @fsockopen($host, 80, $errno, $errstr, $timeout);

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->module->l('Internet Connection Check');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->module->l('Checks if there is an active internet connection');
    }
}

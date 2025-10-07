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
use Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for module version
 */
final class ModuleVersionChecker implements CheckerInterface
{
    /**
     * @var string|null
     */
    private static $lastError = null;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var string
     */
    private $addonsApiUrl = 'https://api.addons.prestashop.com';

    /**
     * @var int
     */
    private $moduleAddonsId = 49791;

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function check(): array
    {
        $currentVersion = $this->module->version;

        $needsUpgrade = $this->checkNeedsUpgrade();

        $latestAddonsVersion = $this->getLatestAddonsVersion();

        $details = [
            'current_version' => $currentVersion,
            'latest_version' => $latestAddonsVersion ?: 'unknown',
            'is_latest' => false,
            'check_error' => $latestAddonsVersion === null,
            'needs_database_upgrade' => $needsUpgrade,
            'addons_version' => $latestAddonsVersion,
        ];

        if (self::$lastError !== null) {
            $details['error_message'] = self::$lastError;
            self::$lastError = null;
        }

        if ($latestAddonsVersion === null) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('Could not check for module updates. Current version is %s'),
                    $currentVersion
                ),
                'details' => $details,
            ];
        }

        if ($needsUpgrade) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('Module database version needs to be upgraded to match file version %s'),
                    $currentVersion
                ),
                'details' => $details,
            ];
        }

        if (version_compare($currentVersion, $latestAddonsVersion, '<')) {
            return [
                'status' => 'warning',
                'message' => sprintf(
                    $this->module->l('Module version %s is outdated. Latest version is %s'),
                    $currentVersion,
                    $latestAddonsVersion
                ),
                'details' => $details,
            ];
        }

        $details['is_latest'] = true;

        return [
            'status' => 'success',
            'message' => sprintf(
                $this->module->l('Module version %s is up to date'),
                $currentVersion
            ),
            'details' => $details,
        ];
    }

    /**
     * @return bool
     */
    private function checkNeedsUpgrade(): bool
    {
        try {
            $module = \Module::getInstanceByName($this->module->name);

            if (!$module) {
                return false;
            }

            return \Tools::version_compare($module->version, $module->database_version, '>');
        } catch (\Exception $e) {
            self::$lastError = 'Error checking module upgrade: ' . $e->getMessage();

            return false;
        }
    }

    /**
     * @return string|null
     */
    private function getLatestAddonsVersion(): ?string
    {
        try {
            $params = sprintf(
                '?format=json&iso_lang=pl&iso_code=pl&method=module&id_module=%d&method=listing&action=module&version=%s',
                $this->moduleAddonsId,
                _PS_VERSION_
            );

            $apiRequest = $this->addonsApiUrl . $params;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $apiRequest);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            $output = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($httpCode === 200 && $output) {
                $apiResponse = json_decode($output);

                return $apiResponse->modules[0]->version ?? null;
            }

            return null;
        } catch (\Exception $e) {
            self::$lastError = 'Error checking version from Addons API: ' . $e->getMessage();

            return null;
        }
    }

    public function getName(): string
    {
        return $this->module->l('Module Version Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the module version is up to date');
    }
}

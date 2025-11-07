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

namespace BluePayment\Test\Checker\Transaction\ITN;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ITNReturnUrlChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function check(): array
    {
        $shopUrl = $this->context->shop->getBaseURL(true);
        $returnUrl = $shopUrl . strtok(\Configuration::get('PS_ROUTE_module'), '/') . '/bluepayment/back';

        $urlAccessibility = $this->checkUrlAccessibility($returnUrl);

        if (!$urlAccessibility['success']) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Return URL is not accessible'),
                'details' => [
                    'return_url' => $returnUrl,
                    'error' => $urlAccessibility['error'],
                    'status_code' => $urlAccessibility['status_code'],
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('Return URL is accessible'),
            'details' => [
                'return_url' => $returnUrl,
                'status_code' => $urlAccessibility['status_code'],
            ],
        ];
    }

    public function getName(): string
    {
        return 'ITN Return URL Check';
    }

    public function getDescription(): string
    {
        return 'Tests if the payment return URL is accessible';
    }

    private function checkUrlAccessibility(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = $statusCode == 302;

        return [
            'success' => $success,
            'status_code' => $statusCode,
            'error' => $success ? null : $error,
        ];
    }
}

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

class FriendlyUrlChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function check(): array
    {
        $friendlyUrlEnabled = (bool) \Configuration::get('PS_REWRITING_SETTINGS');

        if (!$friendlyUrlEnabled) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('Friendly URLs are not enabled in PrestaShop'),
                'details' => [
                    'friendly_url_enabled' => false,
                    'recommendation' => $this->module->l('Enable friendly URLs in PrestaShop Advanced Parameters > SEO & URLs for optimal module operation'),
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('Friendly URLs are enabled in PrestaShop'),
            'details' => [
                'friendly_url_enabled' => true,
            ],
        ];
    }

    public function getName(): string
    {
        return 'Friendly URL Check';
    }

    public function getDescription(): string
    {
        return 'Tests if friendly URLs are enabled in PrestaShop';
    }
}

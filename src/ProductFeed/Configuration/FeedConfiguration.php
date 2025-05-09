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

namespace BlueMedia\ProductFeed\Configuration;

use BluePayment\Config\Config;
use BluePayment\Until\Helper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FeedConfiguration
{
    public const AP_SUFFIX_ENABLED_PRODUCT_FEED = '_DISABLE_PRODUCT_FEED';

    /**
     * @var \BluePayment
     */
    protected $module;

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('bluepayment');
    }

    public function getParameters($currencyIsoCode)
    {
        return [
            'ecommerce' => 'prestashop',
            'ecommerce_version' => _PS_VERSION_,
            'programming_language_version' => PHP_VERSION,
            'plugin_name' => $this->module->name,
            'plugin_version' => $this->module->version,
            'service_id' => Helper::parseConfigByCurrency($this->module->name_upper . Config::SERVICE_PARTNER_ID, $currencyIsoCode),
        ];
    }
}

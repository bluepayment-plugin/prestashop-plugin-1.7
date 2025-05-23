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

if (!defined('_PS_VERSION_')) {
    exit;
}
class XmlDataConfiguration
{
    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function getTitle(): string
    {
        return $this->module->l('Title XML');
    }

    public function getDescription(): string
    {
        return $this->module->l('Description XML');
    }

    public function getShopUrl(): string
    {
        return \Context::getContext()->link->getBaseLink();
    }
}

<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BlueTabPayment
{
    private $xml;

    public function __construct()
    {
        $this->xml = @simplexml_load_file(_PS_ROOT_DIR_ . Module::CACHE_FILE_TAB_MODULES_LIST);
    }

    public function addTab()
    {

        if (empty($this->xml)) {
            return;
        }

        if ($this->xml->xpath('//module[@name="bluepayment"]')) {
            return;
        }

        $highestPosition = 0;

        if (!empty($this->xml)) {
            foreach ($this->getXmlModules() as $module) {
                foreach ($module->attributes() as $name => $attribute) {
                    if ($name == 'position' && $attribute[0] > $highestPosition) {
                        $highestPosition = (int)$attribute[0];
                    }
                }
            }
        }

        $highestPosition++;

        @$modules = $this->xml->xpath('//tab[@class_name="AdminPayment"]');
        $modules = $modules[0];

        if (empty($modules)) {
            return;
        }

        $module = $modules->addChild('module');
        $module->addAttribute('name', 'bluepayment');
        $module->addAttribute('position', $highestPosition);
        $this->xml->saveXML(_PS_ROOT_DIR_ . Module::CACHE_FILE_TAB_MODULES_LIST);
    }

    public function removeTab()
    {

        if (!empty($this->xml)) {
            foreach ($this->getXmlModules() as $key => $module) {
                foreach ($module->attributes() as $attribute) {
                    if ($attribute == 'bluepayment') {
                        var_dump($this->getXmlModules()[$key]);
                        unset($this->getXmlModules()[$key]);
                    }
                }
            }

            $this->xml->saveXML(_PS_ROOT_DIR_ . Module::CACHE_FILE_TAB_MODULES_LIST);
            Tools::clearXMLCache();
        }
    }

    public function getXmlModules()
    {
        return $this->xml->xpath('//tab[@class_name="AdminPayment"]/module');
    }
}

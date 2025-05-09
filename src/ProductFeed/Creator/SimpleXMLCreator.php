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

namespace BlueMedia\ProductFeed\Creator;

if (!defined('_PS_VERSION_')) {
    exit;
}
class SimpleXMLCreator extends \SimpleXMLElement
{
    public function addCData($name, $value = null)
    {
        $newChild = $this->addChild($name);

        if ($newChild !== null) {
            $node = dom_import_simplexml($newChild);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($value));
        }

        return $newChild;
    }
}

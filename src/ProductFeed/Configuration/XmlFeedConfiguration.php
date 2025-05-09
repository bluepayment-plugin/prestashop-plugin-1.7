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
class XmlFeedConfiguration
{
    const GOOGLE_MERCHANT_XML_NAMESPACE = 'http://base.google.com/ns/1.0';

    const AVAILABILITY_IN_STOCK = 'in stock';

    const AVAILABILITY_OUT_OF_STOCK = 'out of stock';
}

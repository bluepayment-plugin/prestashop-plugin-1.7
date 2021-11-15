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

$sql = [];

$sql[] = ' CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blue_gateways` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `gateway_id` int(11) NOT NULL,
                `gateway_status` int(11) NOT NULL,
                `bank_name` varchar(100) NOT NULL,
                `gateway_name` varchar(100) NOT NULL,
                `gateway_description` varchar(1000) DEFAULT NULL,
                `position` int(11) DEFAULT NULL,
                `gateway_currency` varchar(50) NOT NULL,
                `gateway_type` varchar(50) NOT NULL,
                `gateway_logo_url` varchar(500) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=UTF8;';

$sql[] = ' CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blue_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` varchar(256) DEFAULT NULL,
                `remote_id` varchar(128) DEFAULT NULL,
                `amount` DECIMAL(17,2) DEFAULT NULL,
                `currency` varchar(32) DEFAULT NULL,
                `gateway_id` varchar(32) DEFAULT NULL,
                `payment_date` DATETIME DEFAULT NULL,
                `payment_status` varchar(64) DEFAULT NULL,
                `payment_status_details` varchar(128) DEFAULT NULL,
                `blik_status` varchar(32) DEFAULT NULL,
                `blik_code` varchar(32) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=UTF8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

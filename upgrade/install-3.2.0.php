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
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_0($module)
{
    $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blue_gateways_refunds` (
        `id_blue_gateway_refunds` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `order_id` varchar(256) NOT NULL,
        `remote_id` varchar(50) NOT NULL,
        `remote_out_id` varchar(50),
        `status` varchar(50),
        `amount` decimal(17, 2) UNSIGNED DEFAULT 0.0000 NOT NULL,
        `currency` varchar(50) NOT NULL,
        `message_id` varchar(200) NOT NULL,
        `created_at` DATETIME DEFAULT NULL,
        `updated_at` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id_blue_gateway_refunds`)
    ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=UTF8;';

    if (Db::getInstance()->execute($query)) {
        return Db::getInstance()->getMsgError();
    }

    return true;
}

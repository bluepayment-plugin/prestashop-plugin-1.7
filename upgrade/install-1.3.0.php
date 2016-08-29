<?php
/**
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2016
 * @license        https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
 */


if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_1_3_0($object)
{
	return Db::getInstance()->execute(
			'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'blue_gateways` (
                        `gateway_id` int(11) NOT NULL AUTO_INCREMENT,
                        `gateway_status` int(11) NOT NULL,
                        `bank_name` varchar(100) NOT NULL,
                        `gateway_name` varchar(100) NOT NULL,
                        `gateway_description` varchar(1000) DEFAULT NULL,
                        `gateway_sort_order` int(11) DEFAULT NULL,
                        `gateway_type` varchar(50) NOT NULL,
                        `gateway_logo_url` varchar(500) DEFAULT NULL,
                        PRIMARY KEY (`gateway_id`)
                      ) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8;');
}
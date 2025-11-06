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

use BluePayment\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_0($module)
{
    try {
        $createLangTableQuery = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blue_gateway_channels_lang` (
            `id_blue_gateway_channels` int(11) NOT NULL,
            `id_lang` int(10) unsigned NOT NULL,
            `gateway_name` varchar(255) DEFAULT NULL,
            `gateway_description` text DEFAULT NULL,
            `group_title` varchar(255) DEFAULT NULL,
            `group_short_description` text DEFAULT NULL,
            `group_description` text DEFAULT NULL,
            `button_title` varchar(255) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `short_description` text DEFAULT NULL,
            `description_url` varchar(500) DEFAULT NULL,
            PRIMARY KEY (`id_blue_gateway_channels`, `id_lang`),
            KEY `id_blue_gateway_channels` (`id_blue_gateway_channels`),
            KEY `id_lang` (`id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        if (!Db::getInstance()->execute($createLangTableQuery)) {
            PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Failed to create blue_gateway_channels_lang table', 3);

            return false;
        }

        $createTransfersLangTableQuery = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'blue_gateway_transfers_lang` (
            `id` int(11) NOT NULL,
            `id_lang` int(10) unsigned NOT NULL,
            `gateway_name` varchar(255) DEFAULT NULL,
            `gateway_description` text DEFAULT NULL,
            `group_title` varchar(255) DEFAULT NULL,
            `group_short_description` text DEFAULT NULL,
            `group_description` text DEFAULT NULL,
            `button_title` varchar(255) DEFAULT NULL,
            `description` text DEFAULT NULL,
            `short_description` text DEFAULT NULL,
            `description_url` varchar(500) DEFAULT NULL,
            PRIMARY KEY (`id`, `id_lang`),
            KEY `id` (`id`),
            KEY `id_lang` (`id_lang`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        if (!Db::getInstance()->execute($createTransfersLangTableQuery)) {
            PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Failed to create blue_gateway_transfers_lang table', 3);

            return false;
        }

        $columnsToRemove = ['gateway_name', 'gateway_description'];
        $tablesToUpdate = ['blue_gateway_channels', 'blue_gateway_transfers'];

        foreach ($tablesToUpdate as $tableName) {
            foreach ($columnsToRemove as $column) {
                $checkColumnQuery = "SELECT COUNT(*) as column_exists 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = '" . _DB_NAME_ . "' 
                    AND TABLE_NAME = '" . _DB_PREFIX_ . $tableName . "' 
                    AND COLUMN_NAME = '" . $column . "'";

                $columnExists = Db::getInstance()->getValue($checkColumnQuery);

                if ($columnExists > 0) {
                    $dropColumnQuery = 'ALTER TABLE `' . _DB_PREFIX_ . $tableName . '` DROP COLUMN `' . $column . '`';

                    if (!Db::getInstance()->execute($dropColumnQuery)) {
                        PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Failed to drop column ' . $column . ' from ' . $tableName, 3);

                        return false;
                    }
                }
            }
        }

        $addIndexQueries = [
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_channels` ADD INDEX `gateway_id` (`gateway_id`)',
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_channels` ADD INDEX `gateway_currency` (`gateway_currency`)',
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_transfers` ADD INDEX `gateway_id` (`gateway_id`)',
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_transfers` ADD INDEX `gateway_currency` (`gateway_currency`)',
        ];

        foreach ($addIndexQueries as $indexQuery) {
            try {
                Db::getInstance()->execute($indexQuery);
            } catch (Exception $e) {
                PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Index might already exist: ' . $e->getMessage(), 1);
            }
        }

        $addColumnsQueries = [
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_channels` 
                ADD COLUMN `available_for` VARCHAR(10) NULL DEFAULT NULL AFTER `max_amount`,
                ADD COLUMN `required_params` TEXT NULL DEFAULT NULL AFTER `available_for`',
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_transfers` 
                ADD COLUMN `available_for` VARCHAR(10) NULL DEFAULT NULL AFTER `gateway_logo_url`,
                ADD COLUMN `required_params` TEXT NULL DEFAULT NULL AFTER `available_for`',
        ];

        foreach ($addColumnsQueries as $sql) {
            try {
                Db::getInstance()->execute($sql);
            } catch (Exception $e) {
                PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Column might already exist: ' . $e->getMessage(), 1);
            }
        }

        $addDescriptionUrlQueries = [
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_channels_lang` 
                ADD COLUMN `description_url` VARCHAR(500) NULL DEFAULT NULL AFTER `short_description`',
            'ALTER TABLE `' . _DB_PREFIX_ . 'blue_gateway_transfers_lang` 
                ADD COLUMN `description_url` VARCHAR(500) NULL DEFAULT NULL AFTER `short_description`',
        ];

        foreach ($addDescriptionUrlQueries as $sql) {
            try {
                Db::getInstance()->execute($sql);
            } catch (Exception $e) {
                PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: description_url column might already exist: ' . $e->getMessage(), 1);
            }
        }

        PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Successfully upgraded to multilingual gateway channels', 1);

        return (new Installer($module, $module->getTranslator()))->installTabs();
    } catch (Exception $e) {
        PrestaShopLogger::addLog('BluepaymentUpgrade 3.3.0: Upgrade failed with error: ' . $e->getMessage(), 3);

        return false;
    }
}

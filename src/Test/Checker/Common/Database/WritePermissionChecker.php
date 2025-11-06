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

namespace BluePayment\Test\Checker\Common\Database;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for database write permissions
 */
final class WritePermissionChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Db
     */
    private $db;

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
        $this->db = \Db::getInstance();
    }

    public function check(): array
    {
        $tableName = _DB_PREFIX_ . 'bluepayment_test_' . time();

        try {
            $createResult = $this->db->execute('
                CREATE TABLE IF NOT EXISTS `' . $tableName . '` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `test_value` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
            ');

            if (!$createResult) {
                return [
                    'status' => 'error',
                    'message' => $this->module->l('Database write permission check failed'),
                    'details' => [
                        'write' => false,
                        'error' => $this->module->l('Could not create temporary test table'),
                    ],
                ];
            }

            $insertResult = $this->db->execute('
                INSERT INTO `' . $tableName . '` (`test_value`) VALUES ("test_value")
            ');

            if (!$insertResult) {
                // Try to clean up the table even if insert failed
                $this->db->execute('DROP TABLE IF EXISTS `' . $tableName . '`');

                return [
                    'status' => 'error',
                    'message' => $this->module->l('Database write permission check failed'),
                    'details' => [
                        'write' => false,
                        'error' => $this->module->l('Could not insert test record'),
                    ],
                ];
            }

            // Drop the temporary table
            $dropResult = $this->db->execute('DROP TABLE IF EXISTS `' . $tableName . '`');

            if (!$dropResult) {
                return [
                    'status' => 'error',
                    'message' => $this->module->l('Database write permission check failed'),
                    'details' => [
                        'write' => false,
                        'error' => $this->module->l('Could not drop temporary test table'),
                    ],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->module->l('Database write permission is valid'),
                'details' => [
                    'write' => true,
                ],
            ];
        } catch (\Exception $e) {
            try {
                $this->db->execute('DROP TABLE IF EXISTS `' . $tableName . '`');
            } catch (\Exception $dropEx) {
            }

            return [
                'status' => 'error',
                'message' => $this->module->l('Database write permission check failed'),
                'details' => [
                    'write' => false,
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getName(): string
    {
        return $this->module->l('Database Write Permission Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the database can be written to by creating, modifying and dropping a temporary table');
    }
}

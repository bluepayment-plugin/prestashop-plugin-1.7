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
 * Checker for database read permissions
 */
final class ReadPermissionChecker implements CheckerInterface
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
        try {
            $result = $this->db->executeS('SELECT 1');

            if ($result === false) {
                return [
                    'status' => 'error',
                    'message' => $this->module->l('Database read permission check failed'),
                    'details' => [
                        'read' => false,
                        'error' => $this->module->l('Could not execute a simple SELECT query'),
                    ],
                ];
            }

            return [
                'status' => 'success',
                'message' => $this->module->l('Database read permission is valid'),
                'details' => [
                    'read' => true,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Database read permission check failed'),
                'details' => [
                    'read' => false,
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getName(): string
    {
        return $this->module->l('Database Read Permission Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the database can be read from');
    }
}

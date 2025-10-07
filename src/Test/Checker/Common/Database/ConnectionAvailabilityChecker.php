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
 * Checker for database connection availability
 */
final class ConnectionAvailabilityChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var \Db
     */
    private $db;

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
        $this->db = \Db::getInstance();
    }

    public function check(): array
    {
        if (!$this->db->getLink()) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Could not connect to the database'),
                'details' => [
                    'connection' => false,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('Database connection is available'),
            'details' => [
                'connection' => true,
            ],
        ];
    }

    public function getName(): string
    {
        return $this->module->l('Database Connection Availability Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the database connection is available');
    }
}

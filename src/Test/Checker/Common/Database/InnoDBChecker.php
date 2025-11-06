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
 * Checker for InnoDB database engine
 */
final class InnoDBChecker implements CheckerInterface
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
            $engines = $this->db->executeS('SHOW ENGINES');

            $innodbAvailable = false;

            if ($engines && is_array($engines)) {
                foreach ($engines as $engine) {
                    if (strtolower($engine['Engine']) === 'innodb') {
                        $innodbAvailable = true;
                        break;
                    }
                }
            }

            if (!$innodbAvailable) {
                return [
                    'status' => 'warning',
                    'message' => $this->module->l('InnoDB engine is not available'),
                    'details' => [
                        'innodb' => false,
                        'error' => $this->module->l('InnoDB engine is not available'),
                    ],
                ];
            }

            if (_MYSQL_ENGINE_ !== 'InnoDB') {
                return [
                    'status' => 'warning',
                    'message' => sprintf(
                        $this->module->l('Default database engine is not InnoDB. Current: %s'),
                        _MYSQL_ENGINE_
                    ),
                    'details' => [
                        'innodb' => false,
                        'error' => sprintf(
                            $this->module->l('Default database engine is not InnoDB. Current: %s'),
                            _MYSQL_ENGINE_
                        ),
                    ],
                ];
            }

            /* @phpstan-ignore-next-line */
            return [
                'status' => 'success',
                'message' => $this->module->l('InnoDB engine is available and set as default'),
                'details' => [
                    'innodb' => true,
                    'is_default' => true,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('Could not check InnoDB engine status'),
                'details' => [
                    'innodb' => false,
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getName(): string
    {
        return $this->module->l('Database InnoDB Engine Check');
    }

    public function getDescription(): string
    {
        return $this->module->l('Checks if the InnoDB database engine is available and set as default');
    }
}

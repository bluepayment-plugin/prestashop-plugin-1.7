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

namespace BluePayment\Test\Checker\Common\Php;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Checker for PHP version
 */
final class VersionChecker implements CheckerInterface
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
     * @var string
     */
    private $minPhpVersion = '7.4';

    /**
     * @var string
     */
    private $maxPhpVersion = '8.2';

    /**
     * @param \Module $module
     * @param \Context $context
     */
    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): array
    {
        $phpVersion = phpversion();

        if (version_compare($phpVersion, $this->minPhpVersion, '<')) {
            return [
                'status' => 'error',
                'message' => $this->module->l('Unsupported PHP version installed') . ' (' . $phpVersion . ')',
                'details' => [
                    'version' => $phpVersion,
                    'min_required' => $this->minPhpVersion,
                ],
            ];
        }

        if (version_compare($phpVersion, $this->maxPhpVersion, '>')) {
            return [
                'status' => 'warning',
                'message' => $this->module->l('PHP version not recommended by PrestaShop') . ' (' . $phpVersion . ')',
                'details' => [
                    'version' => $phpVersion,
                    'max_recommended' => $this->maxPhpVersion,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('PHP version is supported') . ' (' . $phpVersion . ')',
            'details' => [
                'version' => $phpVersion,
                'min_required' => $this->minPhpVersion,
                'max_recommended' => $this->maxPhpVersion,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->module->l('PHP Version Check');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->module->l('Checks if the PHP version is supported (7.4-8.2)');
    }
}

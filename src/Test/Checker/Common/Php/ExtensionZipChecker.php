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
 * Checker for PHP ZIP extension
 */
final class ExtensionZipChecker implements CheckerInterface
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): array
    {
        $extensionName = 'zip';
        $isLoaded = extension_loaded($extensionName);

        if (!$isLoaded) {
            return [
                'status' => 'error',
                'message' => $this->module->l('PHP ZIP extension not found. Unable to download log package'),
                'details' => [
                    'extension' => $extensionName,
                    'loaded' => false,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => $this->module->l('PHP extension is installed') . ': ' . $extensionName,
            'details' => [
                'extension' => $extensionName,
                'loaded' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->module->l('PHP ZIP Extension Check');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->module->l('Checks if the PHP ZIP extension is installed');
    }
}

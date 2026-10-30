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

namespace BluePayment\Dependency;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BluePaymentAddons\Prestashop\ModuleLibMboInstaller\DependencyBuilder;

/**
 * Thin wrapper around the prefixed MBO installer's DependencyBuilder.
 *
 * Only exposes the dependency-resolver responsibility (installing
 * ps_accounts/ps_eventbus when missing) — no full CloudSync/PrestaShop
 * Account consent flow.
 */
class DependencyManager
{
    /** @var DependencyBuilder */
    private $dependencyBuilder;

    public function __construct()
    {
        $this->dependencyBuilder = new DependencyBuilder(\Module::getInstanceByName('bluepayment'));
    }

    /**
     * @throws \Exception
     */
    public function areDependenciesMet(): bool
    {
        return $this->dependencyBuilder->areDependenciesMet();
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getDependencies(): array
    {
        return $this->dependencyBuilder->handleDependencies();
    }
}

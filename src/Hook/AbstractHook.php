<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Hook;

use Context;

abstract class AbstractHook
{
    const AVAILABLE_HOOKS = [];

    /**
     * @var \BluePayment
     */
    protected $module;

    /**
     * @var \Context
     */
    protected $context;


    public function __construct(\BluePayment $module)
    {
        $this->module = $module;
        $this->context = $module->getContext();
    }

    /**
     * @return array
     */
    public function getAvailableHooks()
    {
        return static::AVAILABLE_HOOKS;
    }
}

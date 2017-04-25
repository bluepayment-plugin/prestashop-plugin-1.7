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

function upgrade_module_1_3_4($object){

    Configuration::updateValue('BLUEPAYMENT_SHOW_BANER', 0);
    return True;
}
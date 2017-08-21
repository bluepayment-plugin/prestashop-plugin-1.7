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


class BluePaymentGatewayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $gateway_id = Tools::getValue('gateway_id');
        $this->context->cookie->gateway_id = $gateway_id;
        exit;
    }
}
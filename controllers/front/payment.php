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

use BlueMedia\OnlinePayments\Gateway;
use BlueMedia\OnlinePayments\Model\TransactionStandard;

/**
 * @property BluePayment $module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BluePaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @throws PrestaShopException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;

        if ($cart->id_customer === 0
            || $cart->id_address_delivery === 0
            || $cart->id_address_invoice === 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Sprawdzenie czy opcja płatności jest nadal aktywna w przypadku kiedy klient dokona zmiany adresu
        // przed finalizacją zamówienia
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === 'bluepayment') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'bluepayment'));
        }

        // Stworzenie obiektu klienta na podstawie danych z koszyka
        $customer = new Customer($cart->id_customer);

        // Jeśli nie udało się stworzyć i załadować obiektu klient, przekieruj na 1 krok
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false) {
            $cartId = $cart->id;

            $totalPaid = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $amount = number_format(round($totalPaid, 2), 2, '.', '');

            $this->module->validateOrder(
                $cartId,
                Configuration::get($this->module->name_upper . '_STATUS_WAIT_PAY_ID'),
                $amount,
                $this->module->displayName,
                null,
                [],
                null,
                false,
                $customer->secure_key
            );

            $orderId = $this->module->currentOrder . '-' . time();
        } else {
            $bluepaymentCartId = Tools::getValue('bluepayment_cart_id', null);

            if (empty($bluepaymentCartId)) {
                die($this->module->l('This cart is empty.', 'bluepayment'));
            } else {
                $bluepaymentCartId = explode('-', $bluepaymentCartId);
                $bluepaymentCartId = empty($bluepaymentCartId[0]) ? 0 : $bluepaymentCartId[0];

                $order = Order::getByCartId($bluepaymentCartId);
                $cart = Cart::getCartByOrderId($order->id);

                $totalPaid = (float)$cart->getOrderTotal(true, Cart::BOTH);
                $amount = number_format(round($totalPaid, 2), 2, '.', '');

                $orderId = $order->id . '-' . time();
            }
        }

        $isoCode = $this->context->currency->iso_code;


        // Identyfikator serwisu partnera
        $service_id = (int)$this->module
            ->parseConfigByCurrency($this->module->name_upper . '_SERVICE_PARTNER_ID', $isoCode);

        // Klucz współdzielony
        $shared_key = $this->module->parseConfigByCurrency($this->module->name_upper . '_SHARED_KEY', $isoCode);

        $gateway_id = (int)Tools::getValue('bluepayment_gateway', 0);

        require_once dirname(__FILE__) . '/../../sdk/index.php';

        $test_mode = Configuration::get($this->module->name_upper . '_TEST_ENV');
        $gateway_mode = $test_mode ? Gateway::MODE_SANDBOX : Gateway::MODE_LIVE;

        $gateway = new Gateway($service_id, $shared_key, $gateway_mode);

        $transactionStandard = new TransactionStandard();
        $transactionStandard->setOrderId($orderId)
            ->setAmount($amount)
            ->setCustomerEmail($customer->email)
            ->setCurrency($isoCode)
            ->setHtmlFormLanguage($this->context->language->iso_code ? : DEFAULT_PAYMENT_FORM_LANGUAGE)
            ->setLanguage($this->context->language->iso_code ? : DEFAULT_PAYMENT_FORM_LANGUAGE);


        $regulationId = Tools::getValue('bluepayment-hidden-psd2-regulation-id', null);

        // Parametr regulation-id jest przekazywany tylko w przypadku kanałow z regulaminami PSD
        if (empty($regulationId) === false) {
            // Zaakceptowana przez uzytkownika PSD2 klauzula
            $transactionStandard
                ->setDefaultRegulationAcceptanceID(Tools::getValue('bluepayment-hidden-psd2-regulation-id'))
                ->setDefaultRegulationAcceptanceState('ACCEPTED')
                ->setDefaultRegulationAcceptanceTime(date('Y-m-d H:i:s'));
        }

        if ($gateway_id !== 0) {
            $transactionStandard->setGatewayId($gateway_id);
        }

        $form = '';

        /** @var Gateway $gateway */
        try {
            $form = $gateway->doTransactionStandard($transactionStandard);
        } catch (Exception $exception) {
            Tools::error_log($exception);
        }

        Db::getInstance()->insert(
            'blue_transactions',
            [
                'order_id'   => $orderId,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );

        $this->context->smarty->assign([
            'module_dir' => $this->module->getPathUri(),
            'form'       => $form,
        ]);

        $this->setTemplate('module:bluepayment/views/templates/front/payment.tpl');
    }
}

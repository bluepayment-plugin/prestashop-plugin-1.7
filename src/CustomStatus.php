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
 * @todo przepisać tłumaczenia na pl do pliku z tłumaczeniami
 * @todo dodać ikonki statusów
 * @todo dodać walidacje ze jak nie ma statusow to nie probowac usuwac przy deinstalacji
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomStatus
{

    public static function addOrderStates(int $language_id, $base_name)
    {
        $mails_languages = ['pl', 'en'];

        foreach ($mails_languages as $l) {
            if (is_dir(_PS_ROOT_DIR_ . '/mails/' . $l . '/')) {
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_pending.html',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_pending.html'
                );
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_pending.txt',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_pending.txt'
                );
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_completed.html',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_completed.html'
                );
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_completed.txt',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_completed.txt'
                );
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_payment_error.html',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_payment_error.html'
                );
                copy(
                    _PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_payment_error.txt',
                    _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_payment_error.txt'
                );
                //copy(
                //_PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_refund.html',
                // _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_refund.html'
                //);
                //copy(
                //_PS_MODULE_DIR_ . '/bluepayment/mails/' . $l . '/bluemedia_refund.txt',
                // _PS_ROOT_DIR_ . '/mails/' . $l . '/bluemedia_refund.txt'
                //);
            }
        }

        $languages = Language::getLanguages(false);

        //common features for both statuses
        $module_name = 'bluepayment';
        $unremovable = false;


        //features for bluemedia pending status
        $pending_color = '#4997F5';
        $pending_template = 'bluemedia_pending';
        $pending_send_email = true;
        $pending_paid = false;
        $pending_name_en = "Blue Media: payment in progress";
        $pending_name_pl = "Blue Media: płatność w trakcie realizacji";

        //features for completed status
        $completed_color = '#77CB1E';
        $completed_template = 'bluemedia_completed';
        $completed_send_email = true;
        $completed_paid = true;
        $completed_name_en = "Blue Media: payment accepted";
        $completed_name_pl = "Blue Media: płatność zatwierdzona";

        //features for bluemedia payment error status
        $payment_error_color = '#cb1e77';
        $payment_error_template = 'bluemedia_payment_error';
        $payment_error_send_email = true;
        $payment_error_paid = false;
        $payment_error_name_en = "Blue Media: payment error";
        $payment_error_name_pl = "Blue Media: błąd płatności";

        //features for bluemedia payment error status
        //        $refund_color = '#dedede';
        //        $refund_template = 'bluemedia_refund';
        //        $refund_send_email = true;
        //        $refund_paid = false;
        //        $refund_name_en = "Blue Media: refund";
        //        $refund_name_pl = "Blue Media: zwrot płatności";


        if (!CustomStatus::checkIfStateExists($pending_name_pl, $language_id)
            || CustomStatus::checkIfStateExists($pending_name_en, $language_id)) {
            // create new pending state
            $pending = new OrderState();
            $pending->module_name = "$module_name";
            $pending->template = $pending_template;
            $pending->unremovable = $unremovable;
            $pending->color = $pending_color;
            $pending->send_email = $pending_send_email;
            $pending->paid = $pending_paid;

            foreach ($languages as $language) {
                if ($language['iso_code'] == "pl") {
                    $pending->name[$language['id_lang']] = $pending_name_pl;
                } else {
                    $pending->name[$language['id_lang']] = $pending_name_en;
                }
            }


            $pending->add();
            $stateId = $pending->id;
            Configuration::updateValue($base_name . '_STATUS_WAIT_PAY_ID', $stateId);
        }

        if (!CustomStatus::checkIfStateExists($completed_name_pl, $language_id)
            || CustomStatus::checkIfStateExists($completed_name_en, $language_id)) {
            // create new completed state
            $completed = new OrderState();
            $completed->module_name = $module_name;
            $completed->template = $completed_template;
            $completed->invoice = 1;
            $completed->unremovable = 1;
            $completed->color = $completed_color;
            $completed->send_email = $completed_send_email;
            $completed->paid = $completed_paid;

            foreach ($languages as $language) {
                if ($language['iso_code'] == "pl") {
                    $completed->name[$language['id_lang']] = $completed_name_pl;
                } else {
                    $completed->name[$language['id_lang']] = $completed_name_en;
                }
            }

            $completed->add();
            $stateId = $completed->id;
            Configuration::updateValue($base_name . '_STATUS_ACCEPT_PAY_ID', $stateId);
        }

        if (!CustomStatus::checkIfStateExists($payment_error_name_pl, $language_id)
            || CustomStatus::checkIfStateExists($payment_error_name_en, $language_id)) {
            // create new pending state
            $payment_error = new OrderState();
            $payment_error->module_name = "$module_name";
            $payment_error->template = $payment_error_template;
            $payment_error->unremovable = $unremovable;
            $payment_error->color = $payment_error_color;
            $payment_error->send_email = $payment_error_send_email;
            $payment_error->paid = $payment_error_paid;

            foreach ($languages as $language) {
                if ($language['iso_code'] == "pl") {
                    $payment_error->name[$language['id_lang']] = $payment_error_name_pl;
                } else {
                    $payment_error->name[$language['id_lang']] = $payment_error_name_en;
                }
            }


            $payment_error->add();
            $stateId = $payment_error->id;
            Configuration::updateValue($base_name . '_STATUS_ERROR_PAY_ID', $stateId);
        }
        //        if (!CustomStatus::checkIfStateExists($refund_name_pl, $language_id)
        //            || CustomStatus::checkIfStateExists($refund_name_en, $language_id)) {
        //            // create new completed state
        //            $refund = new OrderState();
        //            $refund->module_name = $module_name;
        //            $refund->template = $refund_template;
        //            $refund->invoice = false;
        //            $refund->unremovable = 1;
        //            $refund->color = $refund_color;
        //            $refund->send_email = $refund_send_email;
        //            $refund->paid = $refund_paid;
        //
        //            foreach ($languages as $language) {
        //                if ($language['iso_code'] == "pl") {
        //                    $refund->name[$language['id_lang']] = $refund_name_pl;
        //                } else {
        //                    $refund->name[$language['id_lang']] = $refund_name_en;
        //                }
        //            }
        //
        //            $refund->add();
        //            $stateId = $refund->id;
        //            Configuration::updateValue($base_name . '_STATUS_REFUND_PAY_ID', $stateId);
        //        }
    }

    private static function checkIfStateExists(string $name, $language_id)
    :bool
    {
        $states = OrderState::getOrderStates($language_id);

        foreach ($states as $state) {
            if (in_array($name, $state)) {
                return true;
            }
        }

        return false;
    }

    public static function removeOrderStates()
    {
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'order_state` WHERE module_name=\'bluepayment\'');
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'order_state_lang` WHERE name LIKE \'%Blue%\'');
    }

    public static function getPendingStateID($language_id)
    {

        $states = OrderState::getOrderStates($language_id);

        foreach ($states as $state) {
            if ($state['template'] == 'bluemedia_pending') {
                return $state["id_order_state"];
            }
        }
        return 0;
    }

    public static function getConfirmedStateID($language_id)
    {

        $states = OrderState::getOrderStates($language_id);

        foreach ($states as $state) {
            if ($state['template'] == 'bluemedia_completed') {
                return $state["id_order_state"];
            }
        }
        return 0;
    }
}

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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBluepaymentAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $this->ajax = true;
    }

    public function displayAjaxReloadPaymentsGateway()
    {
        $link = new Link();
        $controller = $link->getAdminLink('AdminBluepaymentPayments');

        $this->ajaxDie(
            Tools::redirectAdmin($controller)
        );
    }

    public function ajaxProcessSaveConfiguration()
    {

        try {
            foreach ($this->module->configFields() as $configField) {
                $value = Tools::getValue($configField, Configuration::get($configField));
                Configuration::updateValue($configField, $value);
            }

            $paymentName = [];
            $paymentGroupName = [];

            foreach (Language::getLanguages(true) as $lang) {
                $paymentName[$lang['id_lang']] =
                    Tools::getValue($this->module->name_upper.'_PAYMENT_NAME_'.$lang['id_lang']);
                $paymentGroupName[$lang['id_lang']] =
                    Tools::getValue($this->module->name_upper.'_PAYMENT_GROUP_NAME_'.$lang['id_lang']);
            }

            $serviceId = [];
            $sharedKey = [];

            foreach ($this->module->getSortCurrencies() as $currency) {
                $serviceId[$currency['iso_code']] =
                    Tools::getValue($this->module->name_upper.'_SERVICE_PARTNER_ID_'.$currency['iso_code']);
                $sharedKey[$currency['iso_code']] =
                    Tools::getValue($this->module->name_upper.'_SHARED_KEY_'.$currency['iso_code']);
            }

            Configuration::updateValue($this->module->name_upper.'_PAYMENT_NAME', $paymentName);
            Configuration::updateValue($this->module->name_upper.'_PAYMENT_GROUP_NAME', $paymentGroupName);
            Configuration::updateValue($this->module->name_upper.'_SERVICE_PARTNER_ID', serialize($serviceId));
            Configuration::updateValue($this->module->name_upper.'_SHARED_KEY', serialize($sharedKey));

            $gateway_payments = new BlueGateway();
            $gateway_payments->syncGateways();

            $gateway_group = new BlueGatewayChannels();
            $gateway_group->syncGateways();

            $this->ajaxDie(Tools::jsonEncode(['success' => true]));
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('BM - Ajax Error', 4);

            $this->ajaxDie(Tools::jsonEncode(['success' => false]));
        }
    }
}

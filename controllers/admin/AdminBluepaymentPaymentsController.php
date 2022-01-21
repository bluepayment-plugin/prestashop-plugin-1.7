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


class AdminBluepaymentPaymentsController extends ModuleAdminController
{

    public function __construct()
    {
        $this->identifier = 'id_blue_gateway_channels';
        $this->bootstrap = true;
        $this->className = 'BlueGatewayChannels';
        $this->table = 'blue_gateway_channels';
        $this->_orderBy = 'position';

        parent::__construct();

        Context::getContext()->smarty->assign('src_img', $this->module->images_dir);

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }


    public function renderView()
    {
        return $this->renderForm();
    }


    public function initContent()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        parent::initContent();

        if (Tools::isSubmit('submit'.$this->module->name)) {
            $gateway_payments = new BlueGateway();
            $gateway_payments->syncGateways();

            $gateway_group = new BlueGatewayChannels();
            $gateway_group->syncGateways();

            $this->context->smarty->assign('confirmation', 'ok');
        }

        if (isset($this->context->cookie->redirect_success_message)) {
            $this->context->smarty->assign([
                'redirect_success_message' => $this->context->cookie->redirect_success_message,
            ]);
            $this->context->cookie->__unset('redirect_success_message');
        }


        if (Tools::getValue('ajax')) {
            if (Tools::getValue('action') == 'updatePositions') {
                //
                $ttt = new BlueGatewayChannels();
                $ttt->updatePosition(
                    Tools::getValue('id'),
                    Tools::getValue('way'),
                    Tools::getValue('id_blue_gateway_channels')
                );
            }
        }

        $this->content .= $this->renderForm();
        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }

    public function renderForm()
    {
        $fields_form = [];
        $id_default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $statuses = OrderState::getOrderStates($id_default_lang);

        $fields_form[0]['form'] = [
            'section' => [
                'title' => $this->l('Authentication')
            ],
            'legend' => [
                'title' => $this->l('TESTING ENVIRONMENT'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Use a test environment'),
                    'name' => $this->module->name_upper.'_TEST_ENV',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                    'help' => $this->l(
                        'It allows you to verify the operation of the module without the need to actually pay
                         for the order (in the test mode, no fees are charged for the order).'
                    ),


                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-primary pull-right',
            ],
        ];


        $fields_form[1]['form'] = [
            'section' => [
                'title' => $this->l('Authentication')
            ],
            'legend' => [
                'title' => $this->l('Authentication'),
            ],

            'input' => [
                [
                    'name' => '',
                    'type' => 'description',
                    'content' => 'module:bluepayment/views/templates/admin/_configure/helpers/form/auth_info.tpl',
                ],
                [
                    'name' => '',
                    'type' => 'description',
                    'content' =>
                        'module:bluepayment/views/templates/admin/_configure/helpers/form/notification_info.tpl',
                ]
            ],

            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-primary pull-right',
            ],

        ];

        foreach ($this->module->getSortCurrencies() as $currency) {
            $fields_form[1]['form']['form_group']['fields'][] = [
                'form' => [
                    'legend' => [
                        'title' =>
                            $currency['name'].' ('.$currency['iso_code'].')',
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('Service partner ID'),
                            'name' => $this->module->name_upper.'_SERVICE_PARTNER_ID_'.$currency['iso_code'],
                            'help' => $this->l('It only contains numbers. It is different for each store'),
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Shared key'),
                            'name' => $this->module->name_upper.'_SHARED_KEY_'.$currency['iso_code'],
                            'help' => $this->l('Contains numbers and lowercase letters. It is used to verify 
                            communication with the payment gateway. It should not be made available to the public'),


                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }


        $fields_form[2]['form'] = [
            'section' => [
                'title' => $this->l('Payment settings')
            ],
            'legend' => [
                'title' => $this->l('VISIBILITY OF PAYMENT METHODS'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Show payment methods in the store'),
                    'name' => $this->module->name_upper.'_SHOW_PAYWAY',
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('The name of the payment module in the store'),
                    'name' => $this->module->name_upper.'_PAYMENT_NAME',
                    'size' => 40,
                    'lang' => true,
                    'help' => $this->l('We recommend that you keep the above name. Changing it may have a negative 
                    impact on the customers understanding of the payment methods.'),
                ],

                [
                    'type' => 'text',
                    'label' => $this->l('The name of the payment module in the store'),
                    'name' => $this->module->name_upper.'_PAYMENT_GROUP_NAME',
                    'size' => 40,
                    'lang' => true,
                    'help' => $this->l('We recommend that you keep the above name. Changing it may have a negative 
                    impact on the customers understanding of the payment methods.'),
                ],

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-primary pull-right',
            ],
        ];


        $fields_form[4]['form'] = [
            'section' => [
                'title' => $this->l('Payment settings')
            ],
            'legend' => [
                'title' => $this->l('Statuses'),
            ],
            'input' => [
                [
                    'type' => 'select',
                    'name' => $this->module->name_upper.'_STATUS_WAIT_PAY_ID',
                    'label' => $this->l('Payment started'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => $this->module->name_upper.'_STATUS_ACCEPT_PAY_ID',
                    'label' => $this->l('Payment approved'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'name' => $this->module->name_upper.'_STATUS_ERROR_PAY_ID',
                    'label' => $this->l('Payment failed'),
                    'options' => [
                        'query' => $statuses,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-primary pull-right',
            ],
            //            'currencies' => Currency::getCurrencies()
        ];

        $helper = new HelperForm();

        // Moduł, token i currentIndex
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminBluepaymentPayments');
        $helper->currentIndex = AdminController::$currentIndex;

        // Domyślny język
        $helper->default_form_language = $id_default_lang;
        $helper->allow_employee_form_lang = $id_default_lang;


        // Tytuł i belka narzędzi
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->module->name;

        $helper->tpl_vars = [
            'fields_value' => $this->module->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm($fields_form);
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->module->name)) {
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
        }

        return $this->renderForm();
    }
}

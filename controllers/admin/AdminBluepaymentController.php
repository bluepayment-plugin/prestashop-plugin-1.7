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

class AdminBluepaymentController extends ModuleAdminController
{
    public $className = 'BlueGateway';
    public $table = 'blue_gateways';
    public $identifier = 'id';
    public $position_identifier = 'gateway_id_to_move';


    public function __construct()
    {

        $this->bootstrap = true;

        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }


    public function initContent()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        if ($this->display == 'edit') {
            $this->content .= $this->renderForm();
            $this->context->smarty->assign([
                'content' => $this->content,
            ]);
        } else {
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules')
            );
        }
    }


    public function renderForm()
    {

        $this->fields_form = [
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Gateway Name'),
                    'name' => 'gateway_name',
                    'rows' => 5,
                    'cols' => 100,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
                'name' => 'submitGateway',
            ],
        ];


        return parent::renderForm();
    }


    public function ajaxProcessGatewayStatusBlueGateways()
    {
        if (!$gateway_id = (int)Tools::getValue('id')) {
            die(json_encode([
                'success' => false,
                'error' => true,
                'text' => $this->l('Failed to update the status'),
            ]));
        }

        $gateway = new BlueGateway($gateway_id);
        if (Validate::isLoadedObject($gateway)) {
            $gateway->gateway_status = (int)$gateway->gateway_status === 1 ? 0 : 1;
            $gateway->save()
                ?
                die(json_encode([
                    'success' => true,
                    'text' => $this->l('The status has been updated successfully'),
                ]))
                :
                die(json_encode([
                    'success' => false,
                    'error' => true,
                    'text' => $this->l('Failed to update the status'),
                ]));
        }
    }


    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Edit Payment channel');
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @return bool|ObjectModel
     */
    public function postProcess()
    {
        if (Tools::getIsset('download_gateway')) {
            $gateway = new BlueGateway();
            $gateway->syncGateways();
        }

        return parent::postProcess();
    }
}

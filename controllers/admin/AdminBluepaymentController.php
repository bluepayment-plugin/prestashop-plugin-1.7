<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

include_once __DIR__.'/../../classes/BlueGateway.php';

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

        $this->fields_form = [
            'input'  => [
                [
                    'type'  => 'text',
                    'label' => $this->l('Gateway Name'),
                    'name'  => 'gateway_name',
                    'rows'  => 5,
                    'cols'  => 100,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
                'name'  => 'submitGateway',
            ],
        ];

        $this->fields_list = [
            'gateway_id'       => [
                'title' => $this->l('Gateway ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'bank_name'        => [
                'title' => $this->l('Bank Name'),
            ],
            'gateway_name'     => [
                'title'   => $this->l('Name'),
                'orderby' => false,
            ],
            'gateway_logo_url' => [
                'title'    => $this->l('Logo'),
                'callback' => 'displayGatewayLogo',
                'orderby'  => false,
            ],
            'gateway_currency' => [
                'title' => $this->l('Currency'),
            ],
            'position'         => [
                'title'      => $this->l('Position'),
                'filter_key' => 'position',
                'position'   => 'position',
                'ajax'       => true,
                'align'      => 'center',
            ],
            'gateway_status'   => [
                'title'   => $this->l('Status'),
                'active'  => 'gateway_status',
                'type'    => 'bool',
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'ajax'    => true,
                'orderby' => false,
            ],
        ];

        $this->_orderBy  = 'position';
        $this->_orderWay = 'ASC';

        $this->addRowAction('edit');
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('Blue Media Payment channels management');
    }

    /**
     * @return bool|ObjectModel
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::getIsset('download_gateway')) {
            $gateway = new BlueGateway();
            $gateway->syncGateways();
        }

        return parent::postProcess();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['back']);
        $this->page_header_toolbar_btn['sync_gateway'] = [
            'href' => self::$currentIndex.'&download_gateway&token='.$this->token,
            'desc' => $this->l('Synchronize gateways'),
            'icon' => 'process-icon-refresh',
        ];
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessGatewayStatusBlueGateways()
    {
        if (!$gateway_id = (int)Tools::getValue('id')) {
            die(json_encode([
                'success' => false,
                'error'   => true,
                'text'    => $this->l('Failed to update the status'),
            ]));
        }

        $gateway = new BlueGateway($gateway_id);
        if (Validate::isLoadedObject($gateway)) {
            $gateway->gateway_status = (int)$gateway->gateway_status === 1 ? 0 : 1;
            $gateway->save()
                ?
                die(json_encode([
                    'success' => true,
                    'text'    => $this->l('The status has been updated successfully'),
                ]))
                :
                die(json_encode([
                    'success' => false,
                    'error'   => true,
                    'text'    => $this->l('Failed to update the status'),
                ]));
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        $gateway_id_to_move = (int)Tools::getValue('id');
        $way                = (int)Tools::getValue('way');
        $positions          = Tools::getValue('blue_gateways');
        $position           = null;

        if (is_array($positions)) {
            foreach ($positions as $key => $value) {
                $pos = explode('_', $value);
                if (isset($pos[2]) && (int)$pos[2] === $gateway_id_to_move) {
                    $position = $key;
                    break;
                }
            }
        }

        $gateway = new BlueGateway($gateway_id_to_move);
        if (Validate::isLoadedObject($gateway)) {
            if (null !== $position && $gateway->updatePosition($way, $position)) {
                die(true);
            }
            die('{"hasError" : true, errors : "Cannot update gateways position"}');
        }

        die('{"hasError" : true, "errors" : "This gateway cannot be loaded"}');
    }

    public static function displayGatewayLogo($gatewayLogo)
    {
        return '<img src="'.$gatewayLogo.'" />';
    }
}

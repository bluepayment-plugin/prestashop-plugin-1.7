<?php
include_once dirname(__FILE__).'/../../classes/BlueGateway.php';

class AdminBluepaymentController extends ModuleAdminController
{

    public function __construct()
	{
		$this->bootstrap = true;
		$this->display = 'view';
//		$this->meta_title = $this->l('Blue Payments Gateway Manager');
		parent::__construct();
		if (!$this->module->active)
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
	}
        
        public function initToolBarTitle()
	{
		$this->toolbar_title[] = $this->l('Administration');
		$this->toolbar_title[] = $this->l('Blue Payments Gateway Manager');
	}
	
	public function initPageHeaderToolbar()
	{
		parent::initPageHeaderToolbar();
		unset($this->page_header_toolbar_btn['back']);

	}
	
	public function renderView()
	{
            $this->tpl_view_vars = array(
                'massage' => array(),
                'error' => array()
            );
            
            if (Tools::getIsset('download_gateway')){
                $gateway = new BlueGateway();
                if ($gateway->syncGateways()){
                    $this->tpl_view_vars['massage'][] = $this->l('Successfull Download Payway');
                } else {
                    $this->tpl_view_vars['error'][] = $this->l('Error Download Payway');
                }
            }
            if (Tools::getIsset('change_status')){
                $gateway = new BlueGateway(Tools::getValue('gateway_id'));
                $gateway->gateway_status = $gateway->gateway_status == 1 ? 0 : 1;
                $gateway->update();
                $this->tpl_view_vars['massage'][] = $this->l('Payway status changed');
            }
            $gateways = new Collection('BlueGateway', $this->context->language->id);
	    $gateways->sqlWhere('gateway_type = '.Configuration::get($this->module->name_upper .'_TEST_MODE'));
            $this->tpl_view_vars['gateways'] = $gateways;
            
            if (version_compare(_PS_VERSION_, '1.5.6.0', '>')){
		$this->base_tpl_view = 'view_list.tpl';
            }
	    return parent::renderView();
	}
}


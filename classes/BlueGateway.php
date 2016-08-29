<?php 
class BlueGateway extends ObjectModel
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;
    
    private $module;
    
    public $gateway_status = null;
    public $gateway_id = null;
    public $bank_name = null;
    public $gateway_name = null;
    public $gateway_description;
    public $gateway_sort_order;
    public $gateway_type;
    public $gateway_logo_url;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'blue_gateways',
        'primary' => 'gateway_id',
        'fields' => array(
            'gateway_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false),
            'gateway_status' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'bank_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 100),
            'gateway_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 100),
            'gateway_description' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 1000),
            'gateway_sort_order' => array('type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'),
            'gateway_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 50, 'required' => true),
            'gateway_logo_url' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 500),
        ),
    );
    
    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id, $id_lang, $id_shop);
        $this->module = new BluePayment();
    }
    public function syncGateways()
    {
        $hashMethod = HASH_ALGORITHM;
        $gatewayListAPIUrl = $this->getGatewayListUrl();
        $serviceId = Configuration::get($this->module->name_upper . '_SERVICE_PARTNER_ID');
        $messageId = $this->randomString(self::MESSAGE_ID_STRING_LENGTH);
        $hashKey = Configuration::get($this->module->name_upper . '_SHARED_KEY');
        
        $loadResult = $this->loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl);
        if ($loadResult){
            foreach ($loadResult->gateway as $gateway){
                $payway = new BlueGateway($gateway->gatewayID);
                $payway->gateway_logo_url  = $gateway->iconURL;
                $payway->bank_name = $gateway->bankName;
                $payway->gateway_status = 1;
                $payway->gateway_name = $gateway->gatewayName;
                $payway->gateway_type = Configuration::get($this->module->name_upper .'_TEST_MODE');
                $payway->force_id = true;
                $payway->gateway_id = $gateway->gatewayID;
                $payway->save();
            }
            return true;
        }
        return false;
    }
    
    private function getGatewayListUrl()
    {
        $mode = Configuration::get($this->module->name_upper .'_TEST_MODE');
        if ($mode) {
            return TEST_ADDRESS_PAYWAY_URL;
        }
        return PROD_ADDRESS_PAYWAY_URL;
    }
    
    private function randomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randstring;
    }
    
    private function loadGatewaysFromAPI($hashMethod, $serviceId, $messageId, $hashKey, $gatewayListAPIUrl)
    {
        $hash = hash($hashMethod, $serviceId . HASH_SEPARATOR . $messageId . HASH_SEPARATOR . $hashKey);
        $data = array(
            'ServiceID' => $serviceId,
            'MessageID' => $messageId,
            'Hash' => $hash
        );
        $fields = (is_array($data)) ? http_build_query($data) : $data;
        try {
            $curl = curl_init($gatewayListAPIUrl);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $curlResponse = curl_exec($curl);
            curl_close($curl);
            if ($curlResponse == 'ERROR') {
                return false;
            } else {
                $response = simplexml_load_string($curlResponse);
                return $response;
            }
        } catch (Exception $e) {
            Tools::error_log($e);
            return false;
        }
    }
}
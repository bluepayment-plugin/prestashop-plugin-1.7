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


use Exception;
use XMLWriter;
use SimpleXMLElement;
use BluePayment\Config\Config;
use BluePayment\Until\Helper;
use Db;

//use GuzzleHttp;


/**
 * @property BluePayment $module
 */
class BluePaymentCountriesAPCModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */

    public $header = ['Content-Type' => 'text/xml; charset=UTF8'];
    public $apiurl;
    protected $api_key;
    protected $ssl_enabled;

    public function initContent()
    {
        parent::initContent();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode('Invalid method');
            exit;
        }
        $this->api_key = Configuration::get($this->module->name_upper . '_APC_WS_KEY');
        $this->ssl_enabled = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $this->db = Db::getInstance();
        $this->apiurl = $this->ssl_enabled . $this->api_key . '@' . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__ . 'api/';

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'country where active = 1';
        $countries = Db::getInstance()->ExecuteS($sql);
        $result = [];
        foreach($countries as $country){
            $api_data = $this->send_to_ws('countries/'.$country['id_country'], 'GET');
            if($api_data){
                $api_data = json_decode($api_data, true)['country'];
                $result[] = [
                    'id' => $api_data['id'],
                    'two_letter_abbreviation' => $api_data['iso_code'],
                    'three_letter_abbreviation' => 'NON',
                    'full_name_locale' => $api_data['name'],
                    'full_name_english' => $api_data['name'],
                    'available_regions' => [],
                    'extension_attributes' => []
                ];
            }

        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);



        exit;
    }

    public function send_to_ws($resource, $method)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiurl . $resource . '?output_format=JSON',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->header,
        ));


        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}

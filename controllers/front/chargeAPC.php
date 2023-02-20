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


use BluePayment\Config\Config;
use BluePayment\Until\Helper;
use Context;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use \PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\ObjectPresenter;

//use GuzzleHttp;


/**
 * @property BluePayment $module
 */
class BluePaymentChargeAPCModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */

    public $cartId;
    public $cart_object;
    public $customerId;
    public $currency;
    public $currency_iso;
    public $productId;
    public $attributeId;
    public $quantity;
    public $apiurl;
    public $input;
    public $guestGroup;
    public $id_shop_group;
    public $id_shop;
    public $apc_data;
    public $tax_enabled;
    public $minimum_order_value;
    protected $context;
    protected $api_key;
    protected $ssl_enabled;
    protected $secure_key;
    public $header = ['Content-Type' => 'text/xml; charset=UTF8'];

    public function initContent()
    {
        parent::initContent();
        $this->guestGroup = Configuration::get('PS_GUEST_GROUP');
        $this->id_shop_group = $this->context->shop->id_shop_group;
        $this->id_shop = $this->context->shop->id;
        $this->tax_enabled = (boolean)Configuration::get('PS_TAX');
        $this->minimum_order_value = Configuration::get('PS_PURCHASE_MINIMUM');
        $this->api_key = Configuration::get($this->module->name_upper . '_APC_WS_KEY');
        $this->ssl_enabled = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $method = Tools::getValue('method');
        $this->apiurl = $this->ssl_enabled . $this->api_key . '@' . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__ . 'api/';
        $this->input = json_decode(file_get_contents('php://input'));
        $this->context = \Context::getContext();

        $this->cartId = Tools::getValue('cartId');
        if ($this->input->cartId) {
            $this->cartId = $this->input->cartId;
            $this->apc_data = $this->apc_table_operations('select');
            $this->cart_object = new Cart($this->input->cartId, $this->apc_data['id_lang']);
            $this->customerId = $this->apc_data['customerId'];
        }
        switch ($method) {
            case 'add-to-cart';
                $this->currency = Tools::getValue('currency');
                $this->currency_iso = Tools::getValue('currency_iso');
                $this->productId = Tools::getValue('productId');
                $this->attributeId = Tools::getValue('attributeId');
                $this->quantity = Tools::getValue('quantity');
                $this->add_to_cart();
                break;
            case 'create-order-from-cart';
                $this->currency = Tools::getValue('currency');
                $this->currency_iso = Tools::getValue('currency_iso');
                $this->create_order_from_cart();
                break;
            case 'get-addresses';
                $this->get_addresses();
                break;
            case 'set-shipping-address';
                $this->set_address('delivery');
                break;
            case 'set-billing-address';
                $this->set_address('invoice');
                break;
            case 'get-shipping-methods';
                $this->get_shipping_methods();
                break;
            case 'set-shipping-method';
                $this->set_shipping_method();
                break;
            case 'place-order';
                $this->place_order();
                break;

            case 'couriers';
                $this->getDeliveryOptions();
                break;
            default;
                header("HTTP/1.0 404 Not Found");
                exit;
                break;
        }
        exit;
    }

    protected function create_address($data = null)
    {

    }

    protected function set_address($type)
    {
        $this->log_incoming_data('set_' . $type . '_address');
        $id_country = CountryCore::getByIso($this->input->address->country_id);
        $this->apc_table_operations('update', ['id_country' => $id_country]);
        if ($this->input->address->telephone == null) {
            $this->log_incoming_data('tworzePustyAdres_'.$type);
            $data = [
                'address' => [
                    'id_country' => $id_country,
                    'alias' => 'APC temp_delivery_' . $this->cartId,
                    'lastname' => 'APC temp',
                    'firstname' => 'APC temp',
                    'address1' => 'APC temp',
                    'city' => 'APC temp',
                    'id_customer' => $this->apc_data['customerId'],
                ]
            ];
            $xml = $this->generate_xml($data);
            $response = json_decode($this->send_to_ws('addresses', 'POST', $xml));
            $id_delivery = $response->address->id;
            $data['address']['alias'] = 'APC temp_invoice_' . $this->cartId;
            $xml = $this->generate_xml($data);
            $response = json_decode($this->send_to_ws('addresses', 'POST', $xml));
            $id_invoice = $response->address->id;
            if ($response->address->id) {
                $data = [
                    'cart' => [
                        'id' => $this->cart_object->id,
                        'id_currency' => $this->cart_object->id_currency,
                        'id_lang' => $this->cart_object->id_lang,
                        'id_shop' => $this->id_shop,
                        'id_customer' => $this->customerId,
                        'secure_key' => $this->secure_key,
                        'id_shop_group' => $this->id_shop_group,
                        'id_address_delivery' => $id_delivery,
                        'id_address_invoice' => $id_invoice,
                    ]
                ];
                $xml = $this->generate_xml($data);
                $this->send_to_ws('carts', 'PUT', $xml);
                $data = [
                    'id_address_delivery' => $id_delivery,
                    'id_address_invoice' => $id_invoice,
                ];
                $this->apc_table_operations('update', $data);
            }
        } else {
            $this->log_incoming_data('uzupelniamAdres_'.$type);
            if ($type == 'delivery') {
                $id_address = $this->cart_object->id_address_delivery;
            } else {
                $id_address = $this->cart_object->id_address_invoice;
            }
            $data = [
                'address' => [
                    'id' => $id_address,
                    'alias' => 'APC temp' . $this->cartId,
                    'id_country' => $id_country,
                    'lastname' => $this->input->address->lastname ? $this->input->address->lastname : 'APC temp',
                    'firstname' => $this->input->address->firstname ? $this->input->address->firstname : 'APC temp',
                    'address1' => $this->input->address->street[0] ? $this->input->address->street[0] : 'APC temp',
                    'city' => $this->input->address->city ? $this->input->address->city : 'APC temp',
                    'phone' => $this->input->address->telephone,
                    'postcode' => $this->input->address->postcode,
                    'id_customer' => $this->apc_data['customerId'],
                ]
            ];
            $this->log_incoming_data('adresDostajeDane_'.$type, print_r($data, true));
            $xml = $this->generate_xml($data);
            $response = $this->send_to_ws('addresses', 'PUT', $xml);
            if ($response->address->id) {
                $data = [
                    'cart' => [
                        'id' => $this->cart_object->id,
                        'id_currency' => $this->cart_object->id_currency,
                        'id_lang' => $this->cart_object->id_lang,
                        'id_address_' . $type => $response->address->id
                    ]
                ];
                $xml = $this->generate_xml($data);
                $this->send_to_ws('carts', 'PUT', $xml);
            }
            if($type == 'delivery'){
                $inp['inpost_phone'] = $this->input->address->telephone;
                $inp['inpost_email'] = $this->input->address->email;
                $this->apc_table_operations('update', $inp);
            }
            $this->apc_table_operations('update', $data);
        }
        if ($this->input->address->email) {
            $this->log_incoming_data('customerEmail', $this->input->address->email);
        }
        if ($this->apc_data['is_guest'] == 1 && $this->input->address->email) {
            $sql = 'update pr_customer set lastname = "' . $this->input->address->lastname . '", firstname = "' . $this->input->address->firstname . '", email = "' . $this->input->address->email . '" where id_customer = "' . $this->apc_data['customerId'] . '"';
            $this->log_incoming_data('sqlupdateusera', $sql);
            Db::getInstance()->update('customer', ['email' => $this->input->address->email, 'lastname' => $this->input->address->lastname, 'firstname' => $this->input->address->firstname], 'id_customer = ' . $this->apc_data['customerId']);
//            $data = [
//                'customer' => [
//                    'id' => $this->apc_data['customerId'],
//                    'email' => $this->input->address->email?$this->input->address->email:'tmpAPC' . (time() + rand(999, 9999)) . '@random-domain-address.com',
//                    'lastname' => $this->input->address->lastname?$this->input->address->lastname:'APCtemp',
//                    'firstname' => $this->input->address->firstname?$this->input->address->firstname:'APCtemp',
//                    'passwd' => MD5(SHA1(rand(time(), time() * rand(1, 500)))),
//                ]
//            ];
//            $xml = $this->generate_xml($data);
//            $response = $this->send_to_ws('customers', 'PUT', $xml);
//            $this->log_incoming_data('customerDATA', $response);
        }
        $this->apc_table_operations('update', [$type . '_address' => json_encode($this->input->address, JSON_UNESCAPED_UNICODE)]);
        $this->apc_data = $this->apc_table_operations('select');
        $sql1 = (
            'update `' . _DB_PREFIX_ . 'address`
            set postcode = "'.$this->input->address->postcode.'",
            company = "'.$this->input->address->company.'",
            firstname = "'.$this->input->address->firstname.'",
            lastname = "'.$this->input->address->lastname.'",
            city = "'.$this->input->address->city.'",
            phone = "'.$this->input->address->telephone.'",
            address1 = "'.implode(' ', $this->input->address->street).'"
            WHERE `id_address` = ' . (int) $this->apc_data['id_address_' . $type]
        );
        Db::getInstance()->executeS($sql1);
        header('Content-Type: text/html; charset=utf-8');
        return true;
    }


    protected function log_incoming_data($name, $value = null)
    {
        if (!$value) {
            $value = file_get_contents('php://input');
        }
        Configuration::updateValue($name . '_' . time(), $value);
    }

    protected function is_greater_than_minimum_value()
    {
        if ($this->minimum_order_value > 0) {
            $discount = $this->context->cart->getDiscountSubtotalWithoutGifts(false);
            $cart_total = $this->context->cart->getOrderTotal(false, $this->context->cart->id_lang) - $discount;
            if ((float)$cart_total >= (float)$this->minimum_order_value) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    private function create_order_from_cart()
    {
        if (!$this->is_greater_than_minimum_value()) {
            $result = [
                'status' => 'invalid',
                'message' => $this->l('Minimum order value: ') . $this->context->currentLocale->formatPrice($this->minimum_order_value, $this->currency_iso) . ' ' . $this->l('net')
            ];
            echo json_encode($result);
            return false;
        }
        $discount = $this->context->cart->getDiscountSubtotalWithoutGifts(true);
        $cart_total = $this->context->cart->getOrderTotal(true, $this->context->cart->id_lang) - $discount;
        $is_guest = $this->try_create_customer();
        $data = [
            'cartId' => $this->context->cart->id,
            'customerId' => $this->customerId,
            'id_lang' => $this->context->language->id,
            'id_currency' => $this->context->currency->id,
            'id_shop' => $this->id_shop,
            'id_shop_group' => $this->id_shop_group,
            'id_address_delivery' => null,
            'id_address_invoice' => null,
            'is_guest' => $is_guest,
        ];
        $this->apc_table_operations('insert', $data);
        $result = [
            'status' => 'valid',
            'cart_id' => $this->context->cart->id,
            'grand_total' => $cart_total,
        ];
        echo json_encode($result);
    }


    private function add_to_cart()
    {
        $is_guest = $this->try_create_customer();
        if (Validate::isLoadedObject($this->context->cart)) {
            Db::getInstance()->executeS(
                'delete FROM `' . _DB_PREFIX_ . 'cart_product`'
                . ' WHERE `id_cart` = ' . (int) $this->context->cart->id
            );
            $this->context->cart->updateQty($this->quantity, $this->productId, $this->attributeId);
            $cart = new Cart($this->context->cart->id);
            $cart->id_currency = $this->context->currency->id;
            $cart->id_lang = $this->context->language->id;
            $cart->id_shop = $this->id_shop;
            $cart->id_shop_group = $this->id_shop_group;
            $cart->id_customer = $this->customerId;
            $cart->secure_key = $this->secure_key;
            $cart->save();

        } else {
            $cart = $this->context->cart;
            $cart->id_currency = $this->context->currency->id;
            $cart->id_lang = $this->context->language->id;
            $cart->id_shop = $this->id_shop;
            $cart->id_customer = $this->customerId;
            $cart->save();

            $this->context->cart = $cart;
            $this->context->cart->updateQty($this->quantity, $this->productId, $this->attributeId);
            $this->context->cookie->id_cart = $cart->id;
        }
        if (!$this->is_greater_than_minimum_value()) {
            foreach ($this->context->cart->getProducts() as $product) {
                $this->context->cart->updateQty(0, $product['id_product'], $product['id_product_attribute']);
            }
            $result = [
                'status' => 'invalid',
                'message' => $this->l('Minimalna wartość zamówienia:') . ' ' . $this->context->currentLocale->formatPrice($this->minimum_order_value, $this->currency_iso) . ' ' . $this->l('netto')
            ];
            echo json_encode($result);
            return false;
        }
        $cart_total = $this->context->cart->getOrderTotal(true, $this->context->cart->id_lang);
        $data = [
            'cartId' => $this->context->cart->id,
            'customerId' => $this->customerId,
            'id_lang' => $this->context->language->id,
            'id_currency' => $this->context->currency->id,
            'id_shop' => $this->id_shop,
            'id_shop_group' => $this->id_shop_group,
            'id_address_delivery' => null,
            'id_address_invoice' => null,
            'is_guest' => $is_guest,
        ];
        if ($this->secure_key) {
            $data['secure_key'] = $this->secure_key;
        }
        $this->apc_table_operations('insert', $data);
        $result = [
            'status' => 'valid',
            'cart_id' => $this->context->cart->id,
            'grand_total' => $cart_total,
        ];
        echo json_encode($result);

    }

    private function get_addresses()
    {
        Configuration::updateValue('get_addresses_' . time(), file_get_contents('php://input'));
        echo json_encode([]);
    }




    private function place_order()
    {
        $discount = $this->cart_object->getDiscountSubtotalWithoutGifts($this->tax_enabled);
        $amount = round($this->cart_object->getOrderTotal(true, $this->cart_object->id_lang) + $this->apc_data['shipping_cost_gross'] - $discount, 2);
//        if($amount < $this->input->amount){
//            $amount = $this->input->amount;
//        }
        $this->log_incoming_data('porownanie_amount', 'input: '.(float)$this->input->amount.', wyliczony: '.(float)$amount);
        $delivery_address = json_decode($this->apc_data['delivery_address']);
        $invoice_address = json_decode($this->apc_data['invoice_address']);
        $sql1 = (
            'update `' . _DB_PREFIX_ . 'address`
            set postcode = "'.$delivery_address->postcode.'",
            company = "'.$delivery_address->company.'",
            firstname = "'.$delivery_address->firstname.'",
            lastname = "'.$delivery_address->lastname.'",
            city = "'.$delivery_address->city.'",
            phone = "'.$delivery_address->telephone.'",
            address1 = "'.implode(' ', $delivery_address->street).'"
            WHERE `id_address` = ' . (int) $this->apc_data['id_address_delivery']
        );
        $sql2 = (
            'update `' . _DB_PREFIX_ . 'address`
            set postcode = "'.$invoice_address->postcode.'",
            company = "'.$invoice_address->company.'",
            firstname = "'.$invoice_address->firstname.'",
            lastname = "'.$invoice_address->lastname.'",
            city = "'.$invoice_address->city.'",
            phone = "'.$invoice_address->telephone.'",
            address1 = "'.implode(' ', $invoice_address->street).'",
            vat_number = "'.$invoice_address->vat_id.'"
            WHERE `id_address` = ' . (int) $this->apc_data['id_address_invoice']
        );
        Db::getInstance()->executeS($sql1);
        Db::getInstance()->executeS($sql2);
        $this->log_incoming_data('sql1_update_address', $sql1);
        $this->log_incoming_data('sql2_update_address', $sql2);
        if ((float)$this->input->amount == (float)$amount) {
            $data = [
                'order' => [
                    'id_address_delivery' => $this->apc_data['id_address_delivery'],
                    'id_address_invoice' => $this->apc_data['id_address_invoice'],
                    'id_cart' => $this->input->cartId,
                    'id_currency' => $this->apc_data['id_currency'],
                    'id_lang' => $this->apc_data['id_lang'],
                    'id_customer' => $this->apc_data['customerId'],
                    'id_carrier' => $this->apc_data['id_shipping'],
                    'module' => 'bluepayment',
                    'payment' => 'Blue Media',
                    'total_paid' => $amount,
                    'total_paid_real' => $amount,
                    'total_products' => $this->cart_object->getOrderTotal(false, $this->cart_object->id_lang) - $this->apc_data['shipping_cost_gross'],
                    'total_products_wt' => $this->cart_object->getOrderTotal(true, $this->cart_object->id_lang) - $this->apc_data['shipping_cost_gross'],
                    'total_shipping' => $this->apc_data['shipping_cost_gross'],
                    'total_shipping_incl' => $this->apc_data['shipping_cost_gross'],
                    'total_shipping_excl' => $this->apc_data['shipping_cost_net'],
                    'conversion_rate' => 1,
                ]
            ];
            $xml = $this->generate_xml($data);
            $current_carrier = Configuration::get('PS_CARRIER_DEFAULT');
            Configuration::updateValue('PS_CARRIER_DEFAULT', $this->apc_data['id_shipping']);
            $order_data = json_decode($this->send_to_ws('orders', 'POST', $xml));
            Configuration::updateValue('PS_CARRIER_DEFAULT', $current_carrier);
            $this->log_incoming_data('response_WS', print_r($order_data, true));
            $orderId = '0';
//            $order_data = null;
            if ($order_data) {
                $orderId = $order_data->order->id;
                $sql3 = (
                    'update `' . _DB_PREFIX_ . 'orders`
            set id_address_delivery = "'.$this->apc_data['id_address_delivery'].'",
            id_address_invoice = "'.$this->apc_data['id_address_invoice'].'"
            WHERE `id_order` = ' . (int) $orderId
                );
                Db::getInstance()->executeS($sql3);
                if($this->apc_data['inpost_locker']){
                    Db::getInstance()->insert(
                        'inpost_cart_choice',
                        [
                            'id_cart' => $this->apc_data['cartId'],
                            'service' => 'inpost_locker_standard',
                            'email' => $this->apc_data['inpost_email'],
                            'phone' => $this->apc_data['inpost_phone'],
                            'point' => $this->apc_data['inpost_locker'],
                        ]
                    );
                }
                Db::getInstance()->insert(
                    'blue_transactions',
                    [
                        'order_id' => $orderId . '-' . time(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'gtag_uid' => '',
                        'amount' => $amount,
                        'gateway_id' => Config::GATEWAY_ID_APC
                    ]
                );
                Db::getInstance()->update('orders', ['id_carrier' => $this->apc_data['id_shipping']], 'id_order = ' . $orderId);
                Db::getInstance()->update('order_carrier', ['id_carrier' => $this->apc_data['id_shipping'], 'shipping_cost_tax_excl' => $this->apc_data['shipping_cost_net'], 'shipping_cost_tax_incl' => $this->apc_data['shipping_cost_gross']], 'id_order = ' . $orderId);
                $status = 'SUCCESS';
                $error_code = '';
                $error_message = '';
            } else {
//                $status = 'SUCCESS';
                $status = 'INVALID';
                $error_code = 'WRONG_ORDER_AMOUNT';
//                $error_code = '';
                $error_message = 'Cant create order';
//                $error_message = '';
            }
        } else {
//            $status = 'SUCCESS';
            $status = 'INVALID';
            $error_code = 'WRONG_ORDER_AMOUNT';
//            $error_code = '';
            $error_message = 'The order amount is different from the shopping cart amount.';
//            $error_message = '';
        }
        $result = json_encode(
            [
                'status' => $status,
                'error_code' => $error_code,
                'error_message' => $error_message,
                'order_data' => [
                    'remote_order_id' => $orderId
                ]
            ]
        );
        Configuration::updateValue('place_order_' . time(), file_get_contents('php://input'));
        Configuration::updateValue('place_orderXML_' . time(), htmlspecialchars($xml));
        Configuration::updateValue('place_orderRESULT_' . time(), $result . 'amount APC: ' . $this->input->amount . ' amount cart: ' . $amount . ' WS resp: ' . print_r($order_data, true));
        header('Content-Type: application/json; charset=utf-8');
        echo $result;
    }

    private function generate_xml($data)
    {
        $xml_data = new SimpleXMLElement('<?xml version="1.0"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"></prestashop>');
        $xml = $this->array_to_xml($data, $xml_data);
        return $xml;
    }

    public function array_to_xml($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
        return $xml_data->asXML();
    }


    private function get_shipping_methods()
    {
        Configuration::updateValue('get_shipping_methods_' . time(), json_encode($this->input));
        $this->cartId = $this->input->cartId;
        $deliveries = $this->getDeliveryOptions();

        $result = $this->delivery_options_to_array($deliveries);
        foreach($result as $key => $line){
            unset($result[$key]['shipping_cost_net']);
            unset($result[$key]['shipping_cost_gross']);
        }
//        echo json_encode($deliveries);

//        echo json_encode($delivery_option_list);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function delivery_options_to_array($delivery_option_list)
    {
        $carrier_arr = [];
        foreach ($delivery_option_list as $address_id => $carrier_key) {
            foreach ($carrier_key as $cak => $carrier_list) {
                foreach($carrier_list['carrier_list'] as $key => $val){
                    $carrier_arr[] = [
                        'carrier_code' => $this->friendly_name($val['instance']->name.' '.$key),
                        'carrier_title' => $val['instance']->name,
                        'method_code' => $this->friendly_name($val['instance']->name.' '.$key),
                        'method_title' => $val['instance']->name,
                        'amount' => number_format($val['price_with_tax'], 2, '.', ''),
                        'shipping_cost_net' => number_format($val['price_without_tax'], 2, '.', ''),
                        'shipping_cost_gross' => number_format($val['price_with_tax'], 2, '.', ''),
                    ];
                }
//                $carrier_arr[] = $carrier_list['total_price_with_tax'];
            }
        }
        return $carrier_arr;
    }

    private function friendly_name($text) {
        $letters = array(
            '–', '—', '"', '"', '"', '\'', '\'', '\'',
            '«', '»', '&', '÷', '>',    '<', '$', '/'
        );

        $text = str_replace($letters, " ", $text);
        $text = str_replace("&", " ", $text);
        $text = str_replace("?", "", $text);
        $text = strtolower(str_replace(" ", "_", $text));

        return transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
    }

    private function set_shipping_method()
    {
        Configuration::updateValue('set_shipping_method_' . time(), file_get_contents('php://input'));
        $shipping_id = end(explode('_', $this->input->methodCode));
        $this->apc_table_operations('update', ['id_shipping' => $shipping_id]);
        if(@$this->input->additional->locker_id){
            $this->apc_table_operations('update', ['inpost_locker' => $this->input->additional->locker_id]);
        }
        $deliveries = $this->getDeliveryOptions();
        $result = $this->delivery_options_to_array($deliveries);
        $shipping_cost_net = 0;
        $shipping_cost_gross = 0;
        foreach($result as $key => $val){
            if($shipping_id == end(explode('_', $val['method_code']))){
                $shipping_cost_net = $val['shipping_cost_net'];
                $shipping_cost_gross = $val['shipping_cost_gross'];
            }
        }
        $this->apc_table_operations('update', ['shipping_cost_net' => $shipping_cost_net, 'shipping_cost_gross' => $shipping_cost_gross]);
        header('Content-Type: text/html; charset=utf-8');
        return true;
    }
    public function getDeliveryOptions()
    {
        $cart = new Cart($this->cartId, $this->apc_data['id_lang']);
        $delivery_option_list = $cart->getDeliveryOptionList();
        return $delivery_option_list;
    }


    public function apc_table_operations($action, $data = null)
    {
        if ($action == 'insert') {
            Db::getInstance()->insert('blue_apc', $data);
        } elseif ($action == 'select') {
            $data = new \DbQuery();
            $data->from('blue_apc');
            $data->where('cartId = ' . $this->cartId);
            $data->select('*');
            $data->orderBy('id', 'desc');
            $data = Db::getInstance()->getRow($data);
            return $data;
        } else {
            Db::getInstance()->update('blue_apc', $data, 'cartId = ' . $this->cartId);
        }
    }



    public function send_to_ws($resource, $method, $xml)
    {
        $curl = curl_init();
        $curl_array = array(
            CURLOPT_URL => $this->apiurl . $resource . '?output_format=JSON',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => $this->header,
        );
        $this->log_incoming_data('CURL_'.$resource, print_r($curl_array, true));
        curl_setopt_array($curl, $curl_array);


        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }


    protected function try_create_customer()
    {
        if ($this->context->customer->isLogged()) {
            $this->customerId = $this->context->customer->id;
            $is_guest = 0;
        } else {
            $customer = new Customer();
            $customer->email = 'tmpAPC' . (time() + rand(999, 9999)) . '@random-domain-address2.com';
            $customer->lastname = 'tmpAPC';
            $customer->firstname = 'tmpAPC';
            $customer->is_guest = true;
            $customer->passwd = MD5(SHA1(rand(time(), time() * rand(1, 500))));
            $customer->add();
            $this->secure_key = $customer->secure_key;
            $this->context->customer = $customer;
            $this->context->smarty->assign('confirmation', 1);
            $this->context->cookie->id_customer = (int)$customer->id;
            $this->context->cookie->customer_lastname = $customer->lastname;
            $this->context->cookie->customer_firstname = $customer->firstname;
            $this->context->cookie->passwd = $customer->passwd;
            $this->context->cookie->logged = 1;
            $this->log_incoming_data('wygenerowany_SC', $this->secure_key);

            if (!Configuration::get('PS_REGISTRATION_PROCESS_TYPE'))
                $this->context->cookie->account_created = 1;
            $customer->logged = 1;
            $this->context->cookie->email = $customer->email;
            $this->context->cookie->is_guest = !Tools::getValue('is_new_customer', 1);
            $this->customerId = $this->context->customer->id;
            $is_guest = 1;
        }
        $this->context->customer = new Customer($this->customerId);
        return $is_guest;
    }
}

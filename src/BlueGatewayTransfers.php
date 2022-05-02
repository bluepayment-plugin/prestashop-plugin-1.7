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

class BlueGatewayTransfers extends ObjectModel implements GatewayInterface
{

    private $module;

    public $id;
    public $gateway_status;
    public $gateway_id;
    public $bank_name;
    public $gateway_name;
    public $position;
    public $gateway_currency;
    public $gateway_type;
    public $gateway_logo_url;

    public static $definition = [
        'table' => 'blue_gateway_transfers',
        'primary' => 'id',
        'fields' => [
            'id' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'gateway_id' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'gateway_status' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'bank_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 100,
            ],
            'gateway_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 100
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isNullOrUnsignedId'
            ],
            'gateway_currency' => [
                'type' => self::TYPE_STRING
            ],
            'gateway_type' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 50,
                'required' => true,
            ],
            'gateway_logo_url' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 500
            ],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        $this->module = new BluePayment();

        if (Shop::isFeatureActive()) {
            Shop::addTableAssociation($this->table, ['type' => 'shop']);
        }
    }

    public function syncGateway($apiGateways, $currency, $position = 0)
    {
        PrestaShopLogger::addLog('sync gateway transfers', 1);

        if ($apiGateways) {
            PrestaShopLogger::addLog('BM - Sync gateway transfers', 1);

            foreach ($apiGateways->getGateways() as $paymentGateway) {
                if ($paymentGateway->getGatewayName() !== 'Kartowa płatność automatyczna') {
                    $payway = self::getByGatewayIdAndCurrency(
                        $paymentGateway->getGatewayId(),
                        $currency['iso_code']
                    );
                    $payway->gateway_logo_url = $paymentGateway->getIconUrl();
                    $payway->bank_name = $paymentGateway->getBankName();
                    $payway->gateway_status = $payway->gateway_status !== null ? $payway->gateway_status : 1;
                    $payway->gateway_name = $paymentGateway->getGatewayName();
                    $payway->gateway_type = 1;
                    $payway->gateway_currency = $currency['iso_code'];
                    $payway->force_id = true;
                    $payway->gateway_id = $paymentGateway->getGatewayId();
                    $payway->position = (int)$position;
                    $payway->save();
                    (int)$position++;
                }
            }

            return (int)$position;
        } else {
            PrestaShopLogger::addLog('BM - Error sync gateway transfers', 1);
        }

        return (int)$position;
    }

    public static function gatewayIsActive($gatewayId, $currency, $ignoreStatus = false)
    {
        $id_shop = Context::getContext()->shop->id;

        $query = new DbQuery();
        $query->from('blue_gateway_transfers', 'gt');
        $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $query->where('gt.gateway_id = '.(int)$gatewayId);
        $query->where('gt.gateway_currency = "'.pSql($currency).'"');
        if (Shop::isFeatureActive()) {
            $query->where('gts.id_shop = '.(int)$id_shop);
        }
        $query->select('gt.id');

        if (!$ignoreStatus) {
            $query->where('gt.gateway_status = 1');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    private static function getByGatewayIdAndCurrency($gatewayId, $currency)
    {
        return new BlueGatewayTransfers(self::gatewayIsActive($gatewayId, $currency, true));
    }
}

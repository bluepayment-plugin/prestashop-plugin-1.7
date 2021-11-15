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

class BlueGateway extends ObjectModel
{
    const FAILED_CONNECTION_RETRY_COUNT = 5;
    const MESSAGE_ID_STRING_LENGTH = 32;

    private $module;

    public $id;
    public $gateway_status;
    public $gateway_id;
    public $bank_name;
    public $gateway_name;
    public $gateway_description;
    public $position;
    public $gateway_currency;
    public $gateway_type;
    public $gateway_logo_url;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition
        = [
            'table'   => 'blue_gateways',
            'primary' => 'id',
            'fields'  => [
                'id'                  => [
                    'type'     => self::TYPE_INT,
                    'validate' => 'isUnsignedId',
                ],
                'gateway_id'          => [
                    'type'     => self::TYPE_INT,
                    'validate' => 'isUnsignedId',
                ],
                'gateway_status'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
                'bank_name'           => [
                    'type'     => self::TYPE_STRING,
                    'validate' => 'isGenericName',
                    'required' => true,
                    'size'     => 100,
                ],
                'gateway_name'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 100],
                'gateway_description' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 1000],
                'position'            => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
                'gateway_currency'    => ['type' => self::TYPE_STRING],
                'gateway_type'        => [
                    'type'     => self::TYPE_STRING,
                    'validate' => 'isGenericName',
                    'size'     => 50,
                    'required' => true,
                ],
                'gateway_logo_url'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 500],
            ],
        ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        $this->module = new BluePayment();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @return void
     */
    public function syncGateways()
    {
        $position = 0;

        foreach (Currency::getCurrencies() as $currency) {
            $position = (int)$this->syncGateway($currency, $position);
        }
    }

    /**
     * @param $currency
     * @param int $position
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @return bool
     */
    private function syncGateway($currency, $position = 0)
    {
        $serviceId = (int)$this->module
            ->parseConfigByCurrency($this->module->name_upper . '_SERVICE_PARTNER_ID', $currency['iso_code']);

        $hashKey = $this->module
            ->parseConfigByCurrency($this->module->name_upper . '_SHARED_KEY', $currency['iso_code']);

        if ($serviceId > 0 && !empty($hashKey)) {
            PrestaShopLogger::addLog('BM - Install gateways', 1);

            $loadResult = $this->loadGatewaysFromAPI($serviceId, $hashKey);

            if ($loadResult) {
                /**
                 * @var \BlueMedia\OnlinePayments\Model\Gateway $paymentGateway
                 */

                foreach ($loadResult->getGateways() as $paymentGateway) {
                    $payway = self::getByGatewayIdAndCurrency($paymentGateway->getGatewayId(), $currency['iso_code']);

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
                    $position++;
                }

                return $position;
            }
        } else {
            PrestaShopLogger::addLog('BM - No gateways', 1);
        }

        return $position;
    }

    private function loadGatewaysFromAPI($serviceId, $hashKey)
    {
        require_once dirname(__FILE__) . '/../sdk/index.php';

        $test_mode = Configuration::get($this->module->name_upper . '_TEST_ENV');
        $gateway_mode = $test_mode ?
            \BlueMedia\OnlinePayments\Gateway::MODE_SANDBOX :
            \BlueMedia\OnlinePayments\Gateway::MODE_LIVE;

        $gateway = new \BlueMedia\OnlinePayments\Gateway(
            $serviceId,
            $hashKey,
            $gateway_mode,
            \BlueMedia\OnlinePayments\Gateway::HASH_SHA256,
            HASH_SEPARATOR
        );

        try {
            $response = $gateway->doPaywayList();

            return $response;
        } catch (\Exception $exception) {
            Tools::error_log($exception);

            return false;
        }
    }

    /**
     * @param $way
     * @param $position
     *
     * @throws PrestaShopDatabaseException
     * @return bool
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT g.`id`, g.`position`
            FROM `' . _DB_PREFIX_ . 'blue_gateways` g
            ORDER BY g.`position` ASC')
        ) {
            return false;
        }
        $moved_gateway = false;
        foreach ($res as $gateway) {
            if ((int)$gateway['id'] === (int)$this->id) {
                $moved_gateway = $gateway;
            }
        }
        if ($moved_gateway === false) {
            return false;
        }

        $result = (Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'blue_gateways` g
            SET g.`position`= g.`position` ' . ($way ? '- 1' : '+ 1') . '
            WHERE g.`position`
            ' . ($way
                    ? '> ' . (int)$moved_gateway['position'] . ' AND g.`position` <= ' . (int)$position
                    : '< ' . (int)$moved_gateway['position'] . ' AND g.`position` >= ' . (int)$position))
            && Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'blue_gateways` g
            SET g.`position` = ' . (int)$position . '
            WHERE g.`id`=' . (int)$moved_gateway['id']));

        return $result;
    }

    /**
     *
     * @return int
     */
    public static function getLastAvailablePosition()
    {
        $query = new DbQuery();
        $query->from('blue_gateways')
            ->orderBy('position DESC')
            ->select('position');
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);

        return $result ? (int)$result['position'] + 1 : 0;
    }

    /**
     * @param      $gatewayId
     * @param      $currency
     * @param bool $ignoreStatus
     *
     * @return int
     */
    public static function gatewayIsActive($gatewayId, $currency, $ignoreStatus = false)
    {
        $query = new DbQuery();
        $query->from('blue_gateways')
            ->where('gateway_id = ' . (int)$gatewayId)
            ->where('gateway_currency = "' . pSql($currency) . '"')
            ->select('id');

        if (!$ignoreStatus) {
            $query->where('gateway_status = 1');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @param $gatewayId
     * @param $currency
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @return BlueGateway
     */
    private static function getByGatewayIdAndCurrency($gatewayId, $currency)
    {
        return new BlueGateway(self::gatewayIsActive($gatewayId, $currency, true));
    }
}

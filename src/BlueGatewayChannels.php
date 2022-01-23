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

class BlueGatewayChannels extends ObjectModel
{

    private $module;

    public $id_blue_gateway_channels;
    public $gateway_status;
    public $gateway_id;
    public $bank_name;
    public $gateway_name;
    public $gateway_description;
    public $position;
    public $gateway_currency;
    public $gateway_payments;
    public $gateway_type;
    public $gateway_logo_url;


    public static $definition
        = [

            'table' => 'blue_gateway_channels',
            'primary' => 'id_blue_gateway_channels',
            'fields' => [
                'id_blue_gateway_channels' => [
                    'type' => self::TYPE_INT,
                    'validate' => 'isUnsignedId',
                ],
                'gateway_id' => [
                    'type' => self::TYPE_INT,
                    'validate' => 'isUnsignedId',
                ],
                'gateway_status' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
                'bank_name' => [
                    'type' => self::TYPE_STRING,
                    'validate' => 'isGenericName',
                    'required' => true,
                    'size' => 100,
                ],
                'gateway_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 100],
                'gateway_description' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 1000],
                'position' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
                'gateway_payments' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'],
                'gateway_currency' => ['type' => self::TYPE_STRING],
                'gateway_type' => [
                    'type' => self::TYPE_STRING,
                    'validate' => 'isGenericName',
                    'size' => 50,
                    'required' => true,
                ],
                'gateway_logo_url' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 500],
            ],
        ];

    public function __construct($id_blue_gateway_channels = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_blue_gateway_channels, $id_lang, $id_shop);
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

        $sortCurrencies = $this->module->getSortCurrencies();

        foreach ($sortCurrencies as $currency) {
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

    private function syncGateway($currency, int $position)
    {
        $serviceId = (int)$this->module
            ->parseConfigByCurrency($this->module->name_upper.'_SERVICE_PARTNER_ID', $currency['iso_code']);
        $hashKey = $this->module
            ->parseConfigByCurrency($this->module->name_upper.'_SHARED_KEY', $currency['iso_code']);

        if ($serviceId > 0 && !empty($hashKey)) {
            PrestaShopLogger::addLog('BM - Install gateways', 1);

            $loadResult = $this->loadGatewaysFromAPI($serviceId, $hashKey);

            if ($loadResult) {
                /// Reset position by currency
                $position = 0;

//                dump($loadResult->getGateways());

                foreach ($loadResult->getGateways() as $paymentGateway) {
                    $payway = self::getByGatewayIdAndCurrency($paymentGateway->getGatewayId(), $currency['iso_code']);

//                                        dump($paymentGateway->getGatewayType());

                    if ($paymentGateway->getGatewayName() == 'BLIK' ||
                        $paymentGateway->getGatewayType() == 'Raty online' ||
                        $paymentGateway->getGatewayName() == 'PBC płatność testowa'
                    ) {
                        $payway->gateway_logo_url = $paymentGateway->getIconUrl();
                        $payway->bank_name = $paymentGateway->getBankName();
                        $payway->gateway_status = $payway->gateway_status !== null ? $payway->gateway_status : 1;
                        $payway->gateway_name = $paymentGateway->getGatewayName();
                        $payway->gateway_type = 1;
                        //                        $payway->gateway_payments = 0;
                        $payway->gateway_currency = $currency['iso_code'];
                        $payway->force_id = true;
                        $payway->gateway_id = $paymentGateway->getGatewayId();
                        $payway->position = (int)$position;
                        $payway->save();
                        $position++;
                    } elseif ($paymentGateway->getGatewayType() == 'Portfel elektroniczny') {
                        if (!$this->gatewayIsActive(999, $currency['iso_code'], true)) {
                            $payway->gateway_logo_url = $this->getCardsIcon();
                            $payway->bank_name = 'Wirtualny portfel';
                            $payway->gateway_status = 1;
                            $payway->gateway_name = 'Wirtualny portfel';
                            $payway->gateway_type = 1;
                            $payway->gateway_currency = $currency['iso_code'];
                            $payway->force_id = true;
                            $payway->gateway_payments = 1;
                            $payway->gateway_id = 999;
                            $payway->position = (int)$position;
                            $payway->save();
                            $position++;
                        }
                    } elseif ($paymentGateway->getGatewayType() == 'Szybki Przelew') {
                        if (!$this->gatewayIsActive(9999, $currency['iso_code'], true)) {
                            $payway->gateway_logo_url = $this->getPaymentsIcon();
                            $payway->bank_name = 'Przelew internetowy';
                            $payway->gateway_status = 1;
                            $payway->gateway_name = 'Przelew internetowy';
                            $payway->gateway_type = 1;
                            $payway->gateway_currency = $currency['iso_code'];
                            $payway->gateway_payments = 1;
                            $payway->force_id = true;
                            $payway->gateway_id = 9999;
                            $payway->position = (int)$position;
                            $payway->save();
                            $position++;
                        }
                    }
                }

                return $position;
            }
        } else {
            PrestaShopLogger::addLog('BM - No gateways', 1);
        }

        return $position;
    }


    private function getPaymentsIcon()
    {
        return $this->module->images_dir.'payments.png';
    }

    private function getCardsIcon()
    {
        return $this->module->images_dir.'cards.png';
    }

    private function loadGatewaysFromAPI($serviceId, $hashKey)
    {
        require_once dirname(__FILE__).'/../sdk/index.php';

        $test_mode = Configuration::get($this->module->name_upper.'_TEST_ENV');
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


    public function updatePosition($id, $way, $position)
    {
        if ($result = Db::getInstance()->executeS(
            'SELECT `id_blue_gateway_channels`, `position` FROM `'._DB_PREFIX_.'blue_gateway_channels` 
        WHERE `id_blue_gateway_channels` = '.(int)$id.' 
        ORDER BY `position` ASC'
        )) {
            // check if dragged row is in the table
            $movedBlock = false;
            foreach ($result as $block) {
                if ((int)$block['id_blue_gateway_channels'] == (int)$id) {
                    $movedBlock = $block;
                }
            }

            if ($movedBlock === false) {
                return false;
            }

            // set positions in the table
            return (Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'blue_gateway_channels` SET `position`= `position`
                '.($way ? '- 1' : '+ 1').
                ' WHERE `position`'.($way ? '> '.(int)$movedBlock['position'].' AND `position` <= '
                .(int)$position : '< '.(int)$movedBlock['position'].' AND `position` >= '.(int)$position))
                && Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'blue_gateway_channels` 
                SET `position` = '.(int)$position.' 
                WHERE `id_blue_gateway_channels`='.(int)$movedBlock['id_blue_gateway_channels'])
            );
        }
        return false;
    }


    /**
     * @return int
     */
    public static function getLastAvailablePosition()
    {
        $query = new DbQuery();
        $query->from('blue_gateway_channels')
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
    public static function gatewayIsActive($gatewayId, $currency, bool $ignoreStatus = false)
    {
        $query = new DbQuery();
        $query->from('blue_gateway_channels')
            ->where('gateway_id = '.(int)$gatewayId)
            ->where('gateway_currency = "'.pSql($currency).'"')
            ->select('id_blue_gateway_channels');

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
     * @return BlueGatewayChannels
     */
    private static function getByGatewayIdAndCurrency($gatewayId, $currency)
    {
        return new BlueGatewayChannels(self::gatewayIsActive($gatewayId, $currency, true));
    }
}

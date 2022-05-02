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

class BlueGatewayChannels extends ObjectModel implements GatewayInterface
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

    public static $definition = [
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
            'gateway_description' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isNullOrUnsignedId'
            ],
            'gateway_payments' => [
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

    public function __construct($id_blue_gateway_channels = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_blue_gateway_channels, $id_lang, $id_shop);
        $this->module = new BluePayment();

        if (Shop::isFeatureActive()) {
            Shop::addTableAssociation($this->table, ['type' => 'shop']);
        }
    }

    public function syncGateway($apiGateways, $currency, $position = 0)
    {
        PrestaShopLogger::addLog('sync gateway channels', 1);

        if ($apiGateways) {
            PrestaShopLogger::addLog('BM - Sync gateway channels', 1);

            /// Reset position by currency
            $position = 0;

            foreach ($apiGateways->getGateways() as $paymentGateway) {
                $payway = self::getByGatewayIdAndCurrency(
                    $paymentGateway->getGatewayId(),
                    $currency['iso_code']
                );

                if ($paymentGateway->getGatewayName() == 'BLIK' ||
                    $paymentGateway->getGatewayType() == 'Raty online' ||
                    $paymentGateway->getGatewayName() == 'Alior Raty' ||
                    ($paymentGateway->getGatewayName() == 'PBC płatność testowa'
                        || $paymentGateway->getGatewayName() == 'Płatność kartą')
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
                } elseif ($paymentGateway->getGatewayName() == 'Apple Pay' ||
                    $paymentGateway->getGatewayName() == 'Google Pay'
                ) {
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
                } else {
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
        } else {
            PrestaShopLogger::addLog('BM - Error sync gateway channels', 1);
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

    public static function getLastAvailablePosition()
    {

        $id_shop = Context::getContext()->shop->id;

        $q = new DbQuery();
        $q->from('blue_gateway_channels');
        $q->orderBy('position DESC');
        if (Shop::isFeatureActive()) {
            $q->where('gs.id_shop = '.(int)$id_shop);
        }
        $q->select('position');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($q, false);

        return $result ? (int)$result['position'] + 1 : 0;
    }

    public static function gatewayIsActive($gatewayId, $currency, $ignoreStatus = false)
    {
        $id_shop = Context::getContext()->shop->id;

        $q = new DbQuery();
        $q->from('blue_gateway_channels', 'gc');
        $q->leftJoin('blue_gateway_channels_shop', 'gs', 'gs.id_blue_gateway_channels = gc.id_blue_gateway_channels');
        $q->where('gc.gateway_id = '.(int)$gatewayId);
        $q->where('gc.gateway_currency = "'.pSql($currency).'"');

        if (Shop::isFeatureActive()) {
            $q->where('gs.id_shop = '.(int)$id_shop);
        }

        $q->select('gc.id_blue_gateway_channels');

        if (!$ignoreStatus) {
            $q->where('gc.gateway_status = 1');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($q);
    }

    private static function getByGatewayIdAndCurrency($gatewayId, $currency)
    {
        return new BlueGatewayChannels(self::gatewayIsActive($gatewayId, $currency, true));
    }
}

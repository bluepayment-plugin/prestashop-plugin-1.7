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

declare(strict_types=1);

namespace BluePayment\Api;

use BlueMedia\OnlinePayments\Model\Gateway as GatewayModel;
use Context;
use DbQuery;
use Db;
use Shop;
use Tools;
use PrestaShopLogger;
use BluePayment;

class BlueGatewayChannels extends \ObjectModel implements GatewayInterface
{
    private $module;

    const TABLE = 'blue_gateway_channels';
    const PRIMARY = 'id_blue_gateway_channels';

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
        'table' => self::TABLE,
        'primary' => self::PRIMARY,
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




    public function getOnlyGroups($group): array
    {
        $gatewayArray = [
            GATEWAY_ID_BLIK,
            GATEWAY_ID_ALIOR,
            GATEWAY_ID_CARD,
            GATEWAY_ID_SMARTNEY
        ];
        return array_filter($group, function ($val) use ($gatewayArray) {
            return in_array($val->getGatewayId(), $gatewayArray);
        });
    }




    public function createTransferPaymentOption(): GatewayModel
    {
        $gateway = new GatewayModel();
        $gateway->setGatewayId((string)GATEWAY_ID_TRANSFER)->setGatewayName((string) 'Przelew internetowy');
        $gateway->setGatewayType('1');
        $gateway->setBankName((string)'Przelew internetowy');
        $gateway->setIconUrl((string)$this->getPaymentsIcon());

        return $gateway;
    }

    public function createWalletPaymentOption(): GatewayModel
    {
        $gateway = new GatewayModel();
        $gateway->setGatewayId((string)GATEWAY_ID_WALLET)->setGatewayName('Wirtualny portfel');
        $gateway->setGatewayType('1');
        $gateway->setBankName('Wirtualny portfel');
        $gateway->setIconUrl($this->getCardsIcon());

        return $gateway;
    }





    public function syncGateway($apiGateways, $currency, $position = 1)
    {

        if ($apiGateways) {
            /// Reset position by currency
            $position = 1;

            $paymentsGroup = $apiGateways->getGateways();
            $paymentOptionsArray = $this->getOnlyGroups($paymentsGroup);
            $paymentOptionsArray[GATEWAY_ID_TRANSFER] = $this->createTransferPaymentOption();
            $paymentOptionsArray[GATEWAY_ID_WALLET] = $this->createWalletPaymentOption();
            foreach ($paymentOptionsArray as $paymentGateway) {
                $payway = self::getByGatewayIdAndCurrency(
                    $paymentGateway->getGatewayId(),
                    $currency['iso_code']
                );

                if (!$this->gatewayIsActive($paymentGateway->getGatewayId(), $currency['iso_code'], true)) {
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
            }
            return $position;
        } else {
            PrestaShopLogger::addLog('BM - Error sync gateway channels', 1);
        }

        return $position;
    }

    private function getPaymentsIcon(): string
    {
        return $this->module->images_dir . 'payments.png';
    }

    private function getCardsIcon(): string
    {
        return $this->module->images_dir . 'cards.png';
    }


    public function getChannelsPositions($id, $shopId)
    {
        $q = new \DbQuery();
        $q->select('gc.id_blue_gateway_channels, gc.position');
        $q->from(self::TABLE, 'gc');
        $q->leftJoin('blue_gateway_channels_shop', 'gcs', 'gcs.id_blue_gateway_channels = gc.id_blue_gateway_channels');
        $q->where('gc.id_blue_gateway_channels = "'.(int)($id).'"');
        if (Shop::isFeatureActive()) {
            $q->where('gcs.id_shop = ' . (int)$shopId);
        }
        $q->orderBy('gc.position ASC');

        return Db::getInstance()->executeS($q);
    }


    public function updatePosition($id, $way, $position): bool
    {
        $id_shop = Context::getContext()->shop->id;
        $result = $this->getChannelsPositions($id, $id_shop);

        if ($result) {
            $movedBlock = false;

            foreach ($result as $block) {
                if ((int)$block['id_blue_gateway_channels'] == (int)$id) {
                    $movedBlock = $block;
                }
            }

            if ($movedBlock === false) {
                return false;
            }

            return (Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'blue_gateway_channels` SET `position`= `position`
                ' . ($way ? '- 1' : '+ 1') .
                    ' WHERE `position`' . ($way ? '> ' . (int)$movedBlock['position'] . ' AND `position` <= '
                        . (int)$position : '< ' . (int)$movedBlock['position'] . ' AND `position` >= ' . (int)$position))
                && Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'blue_gateway_channels` 
                SET `position` = ' . (int)$position . ' 
                WHERE `id_blue_gateway_channels`=' . (int)$movedBlock['id_blue_gateway_channels'])
            );
        }
        return false;
    }




    public static function getLastAvailablePosition()
    {

        $id_shop = Context::getContext()->shop->id;

        $q = new DbQuery();
        $q->from(self::TABLE);
        $q->orderBy('position DESC');
        if (Shop::isFeatureActive()) {
            $q->where('gs.id_shop = ' . (int)$id_shop);
        }
        $q->select('position');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($q, false);

        return $result ? (int)$result['position'] + 1 : 0;
    }

    public static function gatewayIsActive($gatewayId, $currency, $ignoreStatus = false)
    {
        $id_shop = Context::getContext()->shop->id;

        $q = new DbQuery();
        $q->from(self::TABLE, 'gc');
        $q->leftJoin('blue_gateway_channels_shop', 'gs', 'gs.id_blue_gateway_channels = gc.id_blue_gateway_channels');
        $q->where('gc.gateway_id = ' . (int)$gatewayId);
        $q->where('gc.gateway_currency = "' . pSql($currency) . '"');

        if (Shop::isFeatureActive()) {
            $q->where('gs.id_shop = ' . (int)$id_shop);
        }

        $q->select('gc.id_blue_gateway_channels');

        if (!$ignoreStatus) {
            $q->where('gc.gateway_status = 1');
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($q);
    }

    private static function getByGatewayIdAndCurrency($gatewayId, $currency): BlueGatewayChannels
    {
        return new BlueGatewayChannels(self::gatewayIsActive($gatewayId, $currency, true));
    }
}

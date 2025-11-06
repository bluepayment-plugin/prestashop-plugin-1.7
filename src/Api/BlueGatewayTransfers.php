<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Api;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BluePayment\Traits\MultilingualGatewayTrait;

class BlueGatewayTransfers extends \ObjectModel implements GatewayInterface
{
    use MultilingualGatewayTrait;

    private $module;

    public $id;
    public $gateway_status;
    public $gateway_id;
    public $position;
    public $gateway_currency;
    public $gateway_type;
    public $gateway_logo_url;
    public $available_for;
    public $required_params;

    public $gateway_name;
    public $bank_name;
    public $gateway_description;
    public $group_title;
    public $group_short_description;
    public $group_description;
    public $button_title;
    public $description;
    public $short_description;
    public $description_url;

    public static $definition = [
        'table' => 'blue_gateway_transfers',
        'primary' => 'id',
        'multilang' => true,
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
                'required' => true,
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isNullOrUnsignedId',
            ],
            'gateway_currency' => [
                'type' => self::TYPE_STRING,
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
                'size' => 500,
            ],
            'available_for' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 10,
            ],
            'required_params' => [
                'type' => self::TYPE_HTML,
            ],
            'bank_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 255,
            ],

            'gateway_name' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'size' => 255,
            ],
            'gateway_description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'group_title' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'size' => 255,
            ],
            'group_short_description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'group_description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'button_title' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'size' => 255,
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'short_description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'description_url' => [
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isUrl',
                'size' => 500,
            ],
        ],
    ];

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
        $this->module = new \BluePayment();

        if (\Shop::isFeatureActive()) {
            \Shop::addTableAssociation($this->table, ['type' => 'shop']);
        }
    }

    public function syncGateway($apiGateways, $currency, $position = 0): ?BlueGatewayTransfers
    {
        $gatewayTransfersToRemove = $this->getGatewayTransfers($currency['iso_code']);
        if (isset($gatewayTransfersToRemove) && !empty($gatewayTransfersToRemove)) {
            $gatewayTransfersToRemove = array_column($gatewayTransfersToRemove, 'gateway_id');
            $gatewayTransfersToRemove = array_flip($gatewayTransfersToRemove);
        }
        if ($apiGateways && $currency) {
            foreach ($apiGateways->getGateways() as $paymentGateway) {
                if ($paymentGateway->getGatewayName() !== 'Kartowa płatność automatyczna') {
                    $payway = self::getByGatewayIdAndCurrency(
                        $paymentGateway->getGatewayId(),
                        $currency['iso_code']
                    );

                    if (isset($gatewayTransfersToRemove[$paymentGateway->getGatewayId()])) {
                        unset($gatewayTransfersToRemove[$paymentGateway->getGatewayId()]);
                    }

                    $payway->gateway_logo_url = $paymentGateway->getIconUrl();
                    $payway->gateway_status = $payway->gateway_status !== null ? $payway->gateway_status : 1;
                    $payway->gateway_type = 1;
                    $payway->gateway_currency = $currency['iso_code'];
                    $payway->force_id = true;
                    $payway->gateway_id = $paymentGateway->getGatewayId();
                    $payway->bank_name = $paymentGateway->getBankName();

                    $payway->available_for = (string) $paymentGateway->getAvailableFor();
                    $payway->required_params = json_encode($paymentGateway->getRequiredParams(), JSON_UNESCAPED_UNICODE);

                    $multilingualData = $this->mapGatewayToMultilingualData($paymentGateway);
                    foreach ($multilingualData as $langId => $langData) {
                        $payway->gateway_name[$langId] = $langData['gateway_name'];
                        $payway->gateway_description[$langId] = $langData['gateway_description'];
                        $payway->group_title[$langId] = $langData['group_title'];
                        $payway->group_short_description[$langId] = $langData['group_short_description'];
                        $payway->group_description[$langId] = $langData['group_description'];
                        $payway->button_title[$langId] = $langData['button_title'];
                        $payway->description[$langId] = $langData['description'];
                        $payway->short_description[$langId] = $langData['short_description'];
                        $payway->description_url[$langId] = $langData['description_url'];
                    }

                    if (!$this->isTransferActive($paymentGateway->getGatewayId(), $currency['iso_code'])) {
                        if (!$payway->id) {
                            $maxPosition = $this->getMaxTransfersPositionsByCurrency($currency);
                            $payway->position = $maxPosition ? $maxPosition + 1 : 0;
                        }
                    }

                    $payway->save();
                    (int) $position++;
                }
            }

            foreach ($gatewayTransfersToRemove as $idGateway => $gatewayTransferToRemove) {
                $this->removeGatewayByCurency($idGateway, $currency);
            }
            $this->autoUpdatePosition($currency);
        }

        \PrestaShopLogger::addLog('BM - Error sync gateway transfers', 1);

        return null;
    }

    public static function getTransferId($gatewayId, $currency)
    {
        $idShop = \Context::getContext()->shop->id;

        $query = new \DbQuery();
        $query->select('gt.id');
        $query->from('blue_gateway_transfers', 'gt');
        $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $query->where('gt.gateway_id = ' . (int) $gatewayId);
        $query->where('gt.gateway_currency = "' . pSql($currency) . '"');
        $query->where('gt.gateway_status = 1');
        if (\Shop::isFeatureActive()) {
            $query->where('gts.id_shop = ' . (int) $idShop);
        }

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * @param $gatewayId
     * @param $currency
     *
     * @return bool
     */
    public static function isTransferActive($gatewayId, $currency): bool
    {
        return (bool) self::getTransferId($gatewayId, $currency);
    }

    private static function getByGatewayIdAndCurrency($gatewayId, $currency): BlueGatewayTransfers
    {
        return new BlueGatewayTransfers(self::getTransferId($gatewayId, $currency));
    }

    public function removeGatewayCurrency($currency): bool
    {
        return \Db::getInstance()->delete('blue_gateway_transfers', 'gateway_currency = "' . $currency['iso_code'] . '"');
    }

    public function removeGatewayByCurency($idGatewayTransfers, $currency): bool
    {
        return \Db::getInstance()->delete('blue_gateway_transfers', 'gateway_id = ' . (int) $idGatewayTransfers . ' AND gateway_currency = "' . pSQL($currency['iso_code']) . '"');
    }

    public function getGatewayTransfers($isoCodeCurrency)
    {
        $idShop = \Context::getContext()->shop->id;

        $query = new \DbQuery();
        $query->select('gt.gateway_id');
        $query->from('blue_gateway_transfers', 'gt');
        $query->leftJoin('blue_gateway_transfers_shop', 'gts', 'gts.id = gt.id');
        $query->where('gt.gateway_currency = "' . pSql($isoCodeCurrency) . '"');
        $query->where('gt.gateway_status = 1');
        if (\Shop::isFeatureActive()) {
            $query->where('gts.id_shop = ' . (int) $idShop);
        }

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    public function autoUpdatePosition($currency)
    {
        $channelsPosition = $this->getTransfersPositionsByCurrency($currency);
        $i = 0;
        foreach ($channelsPosition as $chanelPosition) {
            if ($chanelPosition['position'] != $i) {
                \Db::getInstance()->update(
                    'blue_gateway_transfers',
                    ['position' => $i],
                    'id = ' . (int) $chanelPosition['id'] . ' AND gateway_currency = "' . pSQL($currency['iso_code']) . '"');
            }
            ++$i;
        }
    }

    public function getTransfersPositionsByCurrency($currency)
    {
        $q = new \DbQuery();
        $q->select('gt.id, gt.position');
        $q->from('blue_gateway_transfers', 'gt');
        $q->where('gt.gateway_currency = "' . pSQL($currency['iso_code']) . '"');
        $q->orderBy('gt.position ASC');

        return \Db::getInstance()->executeS($q);
    }

    public function getMaxTransfersPositionsByCurrency($currency)
    {
        $q = new \DbQuery();
        $q->select('MAX(gt.position)');
        $q->from('blue_gateway_transfers', 'gt');
        $q->where('gt.gateway_currency = "' . pSQL($currency['iso_code']) . '"');

        return \Db::getInstance()->getValue($q);
    }
}

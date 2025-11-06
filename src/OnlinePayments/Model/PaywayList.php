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

namespace BlueMedia\OnlinePayments\Model;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BlueMedia\OnlinePayments\Util\Validator;

class PaywayList extends AbstractModel
{
    /**
     * Service id.
     *
     * @var int
     */
    private $serviceId = 0;

    /**
     * Message id.
     *
     * @var string
     */
    private $messageId = '';

    /**
     * Gateways.
     *
     * @var array
     */
    private $gateway = [];

    /**
     * Hash.
     *
     * @var string
     */
    private $hash = '';

    /**
     * Sets serviceID.
     *
     * @param string $serviceId
     *
     * @return $this
     */
    public function setServiceId($serviceId)
    {
        Validator::validateServiceId((string) $serviceId);
        $this->serviceId = (int) $serviceId;

        return $this;
    }

    /**
     * Returns serviceID.
     *
     * @return int
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Sets messageID.
     *
     * @param string $messageId
     *
     * @return $this
     */
    public function setMessageId($messageId)
    {
        $this->messageId = (string) $messageId;

        return $this;
    }

    /**
     * Returns messageID.
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    public function addGateway(Gateway $gateway)
    {
        $this->gateway[$gateway->getGatewayId()] = $gateway;

        return $this;
    }

    public function getGateways()
    {
        return $this->gateway;
    }

    /**
     * Sets hash.
     *
     * @param string $hash
     *
     * @return $this
     */
    public function setHash($hash)
    {
        Validator::validateHash($hash);
        $this->hash = (string) $hash;

        return $this;
    }

    /**
     * Returns hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Validates model.
     *
     * @param string $serviceId
     * @param string $messageId
     *
     * @throws \DomainException
     */
    public function validate($serviceId = '', $messageId = '')
    {
        if (empty($this->serviceId)) {
            throw new \DomainException('ServiceId cannot be empty');
        }
        if (empty($this->messageId)) {
            throw new \DomainException('MessageId cannot be empty');
        }
        if ((string) $this->serviceId !== (string) $serviceId) {
            throw new \DomainException(sprintf('Not equal ServiceId, $this->serviceId: "%s", $serviceId: "%s"', $this->serviceId, $serviceId));
        }
        if ((string) $this->messageId !== (string) $messageId) {
            throw new \DomainException(sprintf('Not equal MessageId, $this->messageId: "%s", $messageId: "%s"', $this->messageId, $messageId));
        }
    }

    /**
     * Creates PaywayList model from multilingual API responses.
     *
     * @param array $languageResponses Array of JSON responses keyed by language code
     *
     * @return PaywayList
     */
    public static function createFromMultiLanguageResponse(array $languageResponses)
    {
        $model = new self();
        $gatewayIds = [];
        $gatewayGroupsMap = [];

        foreach ($languageResponses as $language => $json) {
            if ($json->serviceID) {
                $model->setServiceId((string) $json->serviceID);
            }

            if ($json->messageID) {
                $model->setMessageId((string) $json->messageID);
            }

            if (isset($json->gatewayGroups) && is_array($json->gatewayGroups)) {
                foreach ($json->gatewayGroups as $group) {
                    $groupType = (string) $group->type;

                    if (!isset($gatewayGroupsMap[$groupType])) {
                        $gatewayGroupsMap[$groupType] = [];
                    }

                    $gatewayGroupsMap[$groupType][$language] = $group;
                }
            }

            if (isset($json->gatewayList) && is_array($json->gatewayList)) {
                foreach ($json->gatewayList as $gatewayData) {
                    $gatewayIds[(string) $gatewayData->gatewayID] = true;
                }
            }
        }

        foreach (array_keys($gatewayIds) as $gatewayId) {
            $gateway = Gateway::createFromMultiLanguageResponse($languageResponses, $gatewayId, $gatewayGroupsMap);
            if ($gateway !== null) {
                $model->addGateway($gateway);
            }
        }

        return $model;
    }
}

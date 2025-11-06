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
class Gateway extends AbstractModel
{
    public const GATEWAY_ID_CARD = 1500;
    public const GATEWAY_ID_MTRANSFER = 3;
    public const GATEWAY_ID_MULTITRANSFER = 17;
    public const GATEWAY_ID_BZWBK = 27;
    public const GATEWAY_ID_BPH = 33;
    public const GATEWAY_ID_PEKAO24PRZELEW = 52;
    public const GATEWAY_ID_PEOPAY = 1037;
    public const GATEWAY_ID_CA_ONLINE = 59;
    public const GATEWAY_ID_R_PRZELEW = 76;
    public const GATEWAY_ID_EUROBANK = 79;
    public const GATEWAY_ID_ING = 68;
    public const GATEWAY_ID_MILLENNIUM = 85;
    public const GATEWAY_ID_BOS = 86;
    public const GATEWAY_ID_MERITUM_BANK = 87;
    public const GATEWAY_ID_CITI_HANDLOWY = 90;
    public const GATEWAY_ID_ALIOR_BANK = 95;
    public const GATEWAY_ID_PBS_BANK = 98;
    public const GATEWAY_ID_NETBANK = 99;
    public const GATEWAY_ID_POCZTOWY24 = 108;
    public const GATEWAY_ID_TOYOTA_BANK = 117;
    public const GATEWAY_ID_PLUS_BANK = 131;
    public const GATEWAY_ID_GETIN_BANK = 513;
    public const GATEWAY_ID_DEUTSCHE_BANK = 1002;
    public const GATEWAY_ID_BNP_PARIBAS = 1035;
    public const GATEWAY_ID_IPKO = 1063;
    public const GATEWAY_ID_INTELIGO = 1064;
    public const GATEWAY_ID_IKO = 1065;
    public const GATEWAY_ID_VOLKSWAGEN_BANK = 21;
    public const GATEWAY_ID_SPOLDZIELCZA_GRUPA_BANKOWA = 35;
    public const GATEWAY_ID_BGZ = 71;
    public const GATEWAY_ID_OTHER = 9;
    public const GATEWAY_ID_BLIK = 509;
    public const GATEWAY_ID_BLIK_LATER = 523;
    public const GATEWAY_ID_VISA_CHECKOUT = 1511;
    public const GATEWAY_ID_GOOGLE_PAY = 1512;
    public const GATEWAY_ID_APPLE_PAY = 1513;

    public const GATEWAY_ID_IFRAME = 1506;

    public const GATEWAY_TYPE_PBL = 'PBL';
    public const GATEWAY_TYPE_FAST_TRANSFER = 'Szybki przelew';

    /**
     * Cards gateways.
     *
     * @var array
     */
    private $gatewayTypesCard
        = [
            self::GATEWAY_ID_CARD => 1,
        ];

    /**
     * PBL gateways.
     *
     * @var array
     */
    private $gatewayTypesPbl
        = [
            self::GATEWAY_ID_MTRANSFER => 1,
            self::GATEWAY_ID_MULTITRANSFER => 1,
            self::GATEWAY_ID_BZWBK => 1,
            self::GATEWAY_ID_BPH => 1,
            self::GATEWAY_ID_PEKAO24PRZELEW => 1,
            self::GATEWAY_ID_PEOPAY => 1,
            self::GATEWAY_ID_CA_ONLINE => 1,
            self::GATEWAY_ID_R_PRZELEW => 1,
            self::GATEWAY_ID_EUROBANK => 1,
            self::GATEWAY_ID_ING => 1,
            self::GATEWAY_ID_MILLENNIUM => 1,
            self::GATEWAY_ID_BOS => 1,
            self::GATEWAY_ID_MERITUM_BANK => 1,
            self::GATEWAY_ID_CITI_HANDLOWY => 1,
            self::GATEWAY_ID_ALIOR_BANK => 1,
            self::GATEWAY_ID_PBS_BANK => 1,
            self::GATEWAY_ID_NETBANK => 1,
            self::GATEWAY_ID_POCZTOWY24 => 1,
            self::GATEWAY_ID_TOYOTA_BANK => 1,
            self::GATEWAY_ID_PLUS_BANK => 1,
            self::GATEWAY_ID_GETIN_BANK => 1,
            self::GATEWAY_ID_DEUTSCHE_BANK => 1,
            self::GATEWAY_ID_BNP_PARIBAS => 1,
            self::GATEWAY_ID_IPKO => 1,
            self::GATEWAY_ID_INTELIGO => 1,
            self::GATEWAY_ID_IKO => 1,
            self::GATEWAY_ID_VISA_CHECKOUT => 1,
            self::GATEWAY_ID_GOOGLE_PAY => 1,

            //            self::GATEWAY_ID_SLOVENSKA => 1,
            //            self::GATEWAY_ID_TARTA_BANKA => 1,
            //            self::GATEWAY_ID_VUB_BANKA => 1,
            //            self::GATEWAY_ID_POSTOVA_BANKA => 1,
            //            self::GATEWAY_ID_VIAMO => 1,
        ];

    /**
     * Transfer types.
     *
     * @var array
     */
    private $gatewayTypesTransfer
        = [
            self::GATEWAY_ID_VOLKSWAGEN_BANK => 1,
            self::GATEWAY_ID_SPOLDZIELCZA_GRUPA_BANKOWA => 1,
            self::GATEWAY_ID_BGZ => 1,
            self::GATEWAY_ID_OTHER => 1,
        ];

    /**
     * Gateway id.
     *
     * @var int
     */
    private $gatewayId = 0;

    /**
     * Gateway name (multilingual).
     *
     * @var array
     */
    private $gatewayName = [];

    /**
     * Gateway type.
     *
     * @var string
     */
    private $gatewayType = '';

    /**
     * Bank name.
     *
     * @var string
     */
    private $bankName = '';

    /**
     * Group.
     *
     * @var string
     */
    private $gatewayPayment = '';

    /**
     * Icon URL.
     *
     * @var string
     */
    private $iconUrl = '';

    /**
     * Status date.
     *
     * @var \DateTime
     */
    private $statusDate;

    /**
     * Min amount.
     *
     * @var float
     */
    private $minAmount;

    /**
     * Max amount.
     *
     * @var float
     */
    private $maxAmount;

    /**
     * Button title (multilingual).
     *
     * @var array
     */
    private $buttonTitle = [];

    /**
     * Description (multilingual).
     *
     * @var array
     */
    private $description = [];

    /**
     * Short description (multilingual).
     *
     * @var array
     */
    private $shortDescription = [];

    /**
     * Description URL (multilingual).
     *
     * @var array
     */
    private $descriptionUrl = [];

    /**
     * Available for.
     *
     * @var string
     */
    private $availableFor = '';

    /**
     * Required params.
     *
     * @var array
     */
    private $requiredParams = [];

    /**
     * Group title (multilingual) - from gatewayGroups.
     *
     * @var array
     */
    private $groupTitle = [];

    /**
     * Group short description (multilingual) - from gatewayGroups.
     *
     * @var array
     */
    private $groupShortDescription = [];

    /**
     * Group description (multilingual) - from gatewayGroups.
     *
     * @var array
     */
    private $groupDescription = [];

    /**
     * Returns gateway id.
     *
     * @return int
     */
    public function getGatewayId()
    {
        return $this->gatewayId;
    }

    /**
     * Sets gateway id.
     *
     * @param int $gatewayId
     *
     * @return $this
     */
    public function setGatewayId($gatewayId)
    {
        $this->gatewayId = (int) $gatewayId;

        return $this;
    }

    /**
     * Returns gateway names (all languages).
     *
     * @return array
     */
    public function getGatewayName()
    {
        return $this->gatewayName;
    }

    /**
     * Sets gateway name for specific language.
     *
     * @param string $language
     * @param string $gatewayName
     *
     * @return $this
     */
    public function setGatewayName($language, $gatewayName)
    {
        $this->gatewayName[$language] = (string) $gatewayName;

        return $this;
    }

    /**
     * Gets gateway name for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getGatewayNameForLanguage($language)
    {
        return $this->gatewayName[$language] ?? null;
    }

    /**
     * Gets gateway name for first available language or empty string.
     *
     * @return string
     */
    public function getGatewayNameDefault()
    {
        return !empty($this->gatewayName) ? reset($this->gatewayName) : '';
    }

    /**
     * Returns gateway type.
     *
     * @return string
     */
    public function getGatewayType()
    {
        return $this->gatewayType;
    }

    /**
     * Sets gateway type.
     *
     * @param string $gatewayType
     *
     * @return $this
     */
    public function setGatewayType($gatewayType)
    {
        $this->gatewayType = (string) $gatewayType;

        return $this;
    }

    /**
     * Returns bank name.
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * Sets bank name.
     *
     * @param string $bankName
     *
     * @return $this
     */
    public function setBankName($bankName)
    {
        $this->bankName = (string) $bankName;

        return $this;
    }

    /**
     * Get gateway sub payments.
     *
     * @return string
     */
    public function getGatewayPayment()
    {
        return $this->gatewayPayment;
    }

    /**
     * Sets group
     *
     * @param string $gatewayType
     *
     * @return $this
     */
    public function setGatewayPayment(string $gatewayType)
    {
        $this->gatewayType = (string) $gatewayType;

        return $this;
    }

    /**
     * Returns icon URL.
     *
     * @return string
     */
    public function getIconUrl()
    {
        return $this->iconUrl;
    }

    /**
     * Sets icon URL.
     *
     * @param string $iconUrl
     *
     * @return $this
     */
    public function setIconUrl($iconUrl)
    {
        $this->iconUrl = (string) $iconUrl;

        return $this;
    }

    /**
     * Returns status date.
     *
     * @return \DateTime|null
     */
    public function getStatusDate()
    {
        return $this->statusDate;
    }

    /**
     * Sets status date.
     *
     * @param \DateTime $statusDate
     *
     * @return $this
     */
    public function setStatusDate(\DateTime $statusDate)
    {
        $this->statusDate = $statusDate;

        return $this;
    }

    /**
     * @return float
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param float $minAmount
     */
    public function setMinAmount(float $minAmount)
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    /**
     * @return float
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param float $maxAmount
     */
    public function setMaxAmount(float $maxAmount)
    {
        $this->maxAmount = $maxAmount;

        return $this;
    }

    /**
     * Is gateway a card.
     *
     * @return bool
     */
    public function isCard()
    {
        return array_key_exists($this->gatewayId, $this->gatewayTypesCard);
    }

    /**
     * Is gateway an PBL.
     *
     * @return bool
     */
    public function isPbl()
    {
        return array_key_exists($this->gatewayId, $this->gatewayTypesPbl);
    }

    /**
     * Is gateway a transfer.
     *
     * @return bool
     */
    public function isTransfer()
    {
        return array_key_exists($this->gatewayId, $this->gatewayTypesTransfer);
    }

    /**
     * Returns information if gateway is given gateway id.
     *
     * @param int $gatewayId
     *
     * @return bool
     */
    public function isGateway($gatewayId)
    {
        return $this->gatewayId === $gatewayId;
    }

    /**
     * Returns button titles (all languages).
     *
     * @return array
     */
    public function getButtonTitle()
    {
        return $this->buttonTitle;
    }

    /**
     * Sets button title for specific language.
     *
     * @param string $language
     * @param string $buttonTitle
     *
     * @return $this
     */
    public function setButtonTitle($language, $buttonTitle)
    {
        $this->buttonTitle[$language] = (string) $buttonTitle;

        return $this;
    }

    /**
     * Gets button title for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getButtonTitleForLanguage($language)
    {
        return $this->buttonTitle[$language] ?? null;
    }

    /**
     * Returns descriptions (all languages).
     *
     * @return array
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets description for specific language.
     *
     * @param string $language
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($language, $description)
    {
        $this->description[$language] = (string) $description;

        return $this;
    }

    /**
     * Gets description for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getDescriptionForLanguage($language)
    {
        return $this->description[$language] ?? null;
    }

    /**
     * Returns short descriptions (all languages).
     *
     * @return array
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Sets short description for specific language.
     *
     * @param string $language
     * @param string $shortDescription
     *
     * @return $this
     */
    public function setShortDescription($language, $shortDescription)
    {
        $this->shortDescription[$language] = (string) $shortDescription;

        return $this;
    }

    /**
     * Gets short description for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getShortDescriptionForLanguage($language)
    {
        return $this->shortDescription[$language] ?? null;
    }

    /**
     * Returns description URLs (all languages).
     *
     * @return array
     */
    public function getDescriptionUrl()
    {
        return $this->descriptionUrl;
    }

    /**
     * Sets description URL for specific language.
     *
     * @param string $language
     * @param string $descriptionUrl
     *
     * @return $this
     */
    public function setDescriptionUrl($language, $descriptionUrl)
    {
        $this->descriptionUrl[$language] = (string) $descriptionUrl;

        return $this;
    }

    /**
     * Gets description URL for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getDescriptionUrlForLanguage($language)
    {
        return $this->descriptionUrl[$language] ?? null;
    }

    /**
     * Returns available for.
     *
     * @return string
     */
    public function getAvailableFor()
    {
        return $this->availableFor;
    }

    /**
     * Sets available for.
     *
     * @param string $availableFor
     *
     * @return $this
     */
    public function setAvailableFor($availableFor)
    {
        $this->availableFor = (string) $availableFor;

        return $this;
    }

    /**
     * Returns required params.
     *
     * @return array
     */
    public function getRequiredParams()
    {
        return $this->requiredParams;
    }

    /**
     * Sets required params.
     *
     * @param array $requiredParams
     *
     * @return $this
     */
    public function setRequiredParams(array $requiredParams)
    {
        $this->requiredParams = $requiredParams;

        return $this;
    }

    /**
     * Returns group titles (all languages).
     *
     * @return array
     */
    public function getGroupTitle()
    {
        return $this->groupTitle;
    }

    /**
     * Sets group title for specific language.
     *
     * @param string $language
     * @param string $groupTitle
     *
     * @return $this
     */
    public function setGroupTitle($language, $groupTitle)
    {
        $this->groupTitle[$language] = (string) $groupTitle;

        return $this;
    }

    /**
     * Gets group title for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getGroupTitleForLanguage($language)
    {
        return $this->groupTitle[$language] ?? null;
    }

    /**
     * Returns group short descriptions (all languages).
     *
     * @return array
     */
    public function getGroupShortDescription()
    {
        return $this->groupShortDescription;
    }

    /**
     * Sets group short description for specific language.
     *
     * @param string $language
     * @param string $groupShortDescription
     *
     * @return $this
     */
    public function setGroupShortDescription($language, $groupShortDescription)
    {
        $this->groupShortDescription[$language] = (string) $groupShortDescription;

        return $this;
    }

    /**
     * Gets group short description for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getGroupShortDescriptionForLanguage($language)
    {
        return $this->groupShortDescription[$language] ?? null;
    }

    /**
     * Returns group descriptions (all languages).
     *
     * @return array
     */
    public function getGroupDescription()
    {
        return $this->groupDescription;
    }

    /**
     * Sets group description for specific language.
     *
     * @param string $language
     * @param string $groupDescription
     *
     * @return $this
     */
    public function setGroupDescription($language, $groupDescription)
    {
        $this->groupDescription[$language] = (string) $groupDescription;

        return $this;
    }

    /**
     * Gets group description for specific language.
     *
     * @param string $language
     *
     * @return string|null
     */
    public function getGroupDescriptionForLanguage($language)
    {
        return $this->groupDescription[$language] ?? null;
    }

    /**
     * Creates Gateway model from multilingual API responses.
     *
     * @param array $languageResponses Array of JSON responses keyed by language code
     * @param string $gatewayId Gateway ID to process
     * @param array $gatewayGroupsMap Map of gateway groups by type and language
     *
     * @return Gateway|null
     */
    public static function createFromMultiLanguageResponse(array $languageResponses, $gatewayId, array $gatewayGroupsMap = [])
    {
        $gateway = null;

        foreach ($languageResponses as $language => $json) {
            if (!isset($json->gatewayList) || !is_array($json->gatewayList)) {
                continue;
            }

            foreach ($json->gatewayList as $gatewayData) {
                if ((string) $gatewayData->gatewayID !== (string) $gatewayId) {
                    continue;
                }

                if ($gateway === null) {
                    $gateway = new self();
                    $gateway->setGatewayId((int) $gatewayData->gatewayID);

                    if (isset($gatewayData->groupType)) {
                        $gateway->setGatewayType((string) $gatewayData->groupType);
                    }

                    if (isset($gatewayData->bankName)) {
                        $gateway->setBankName((string) $gatewayData->bankName);
                    }

                    if (isset($gatewayData->gatewayPayment)) {
                        $gateway->setGatewayPayment((string) $gatewayData->gatewayPayment);
                    }

                    if (isset($gatewayData->iconUrl)) {
                        $gateway->setIconUrl((string) $gatewayData->iconUrl);
                    }

                    if (isset($gatewayData->availableFor)) {
                        $gateway->setAvailableFor((string) $gatewayData->availableFor);
                    }

                    if (isset($gatewayData->requiredParams) && is_array($gatewayData->requiredParams)) {
                        $gateway->setRequiredParams($gatewayData->requiredParams);
                    }

                    if (isset($gatewayData->currencies)
                        && is_array($gatewayData->currencies)
                        && isset($gatewayData->currencies[0]->minAmount, $gatewayData->currencies[0]->maxAmount)
                    ) {
                        $gateway->setMinAmount($gatewayData->currencies[0]->minAmount);
                        $gateway->setMaxAmount($gatewayData->currencies[0]->maxAmount);
                    }
                }

                // Mapowanie danych z gatewayGroups na podstawie groupType
                if (isset($gatewayData->groupType) && !empty($gatewayGroupsMap)) {
                    $groupType = (string) $gatewayData->groupType;

                    if (isset($gatewayGroupsMap[$groupType][$language])) {
                        $groupData = $gatewayGroupsMap[$groupType][$language];

                        // Dodajemy pola z gatewayGroups jako osobne pola językowe
                        if (isset($groupData->title) && !empty($groupData->title)) {
                            $gateway->setGroupTitle($language, (string) $groupData->title);
                        }

                        if (isset($groupData->shortDescription) && !empty($groupData->shortDescription)) {
                            $gateway->setGroupShortDescription($language, (string) $groupData->shortDescription);
                        }

                        if (isset($groupData->description) && !empty($groupData->description)) {
                            $gateway->setGroupDescription($language, (string) $groupData->description);
                        }
                    }
                }

                if (isset($gatewayData->name) && !empty($gatewayData->name)) {
                    $gateway->setGatewayName($language, (string) $gatewayData->name);
                }

                if (isset($gatewayData->buttonTitle) && !empty($gatewayData->buttonTitle)) {
                    $gateway->setButtonTitle($language, (string) $gatewayData->buttonTitle);
                }

                if (isset($gatewayData->description) && !empty($gatewayData->description)) {
                    $gateway->setDescription($language, (string) $gatewayData->description);
                }

                if (isset($gatewayData->shortDescription) && !empty($gatewayData->shortDescription)) {
                    $gateway->setShortDescription($language, (string) $gatewayData->shortDescription);
                }

                if (isset($gatewayData->descriptionUrl) && !empty($gatewayData->descriptionUrl)) {
                    $gateway->setDescriptionUrl($language, (string) $gatewayData->descriptionUrl);
                }

                break;
            }
        }

        return $gateway;
    }

    /**
     * Validates model.
     *
     * @throws \DomainException
     */
    public function validate()
    {
        if (empty($this->gatewayId)) {
            throw new \DomainException('GatewayId cannot be empty');
        }
        if (!is_array($this->gatewayName) || empty($this->gatewayName)) {
            throw new \DomainException('GatewayName cannot be empty and must be an array');
        }
    }
}

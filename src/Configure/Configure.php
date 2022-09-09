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

namespace BluePayment\Configure;

use BluePayment\Statuses\CustomStatus;
use Module;
use Symfony\Component\Translation\TranslatorInterface;
use Tab;
use Shop;
use Language;
use PrestaShopLogger;
use Configuration as Cfg;
use BluePayment\Until\Helper;

class Configure
{
    protected $translator;
    protected $module;
    protected $name;

    const TRANSLATE_GROUP = 'Modules.Bluepayment';

    public function __construct(\BluePayment $module, TranslatorInterface $translator)
    {
        $this->module = $module;
        $this->translator = $translator;
        $this->name = \Tools::strtoupper($this->module->name);
    }

    public function install(): bool
    {
       return $this->installConfiguration() && $this->addOrderStatuses();
    }

    public function uninstall(): bool
    {
        return $this->uninstallConfiguration() && $this->removeOrderStatuses();
    }


    /**
     * Create configuration fields
     * @return bool
     */
    public function installConfiguration(): bool
    {
        $res = true;

        if (Shop::isFeatureActive()) {
            foreach (Shop::getContextListShopID() as $shop_id) {
                $group_id = Shop::getGroupFromShop($shop_id, true);

                $res &= Cfg::updateValue($this->name . '_TEST_ENV', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_SHOW_PAYWAY', 1, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_GA_TYPE', 2, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_GA_TRACKER_ID', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_GA4_TRACKER_ID', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_GA4_SECRET', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_BLIK_REDIRECT', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_GPAY_REDIRECT', 0, false, $group_id, $shop_id);

                $res &= Cfg::updateValue($this->name . '_PROMO_PAY_LATER', 1, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_INSTALMENTS', 1, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_MATCHED_INSTALMENTS', 1, false, $group_id, $shop_id);

                $res &= Cfg::updateValue($this->name . '_PROMO_HEADER', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_FOOTER', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_LISTING', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_PRODUCT', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_CART', 0, false, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PROMO_CHECKOUT', 1, false, $group_id, $shop_id);
            }
        } else {
            /* Sets up Global configuration */
            $res = Cfg::updateValue($this->name . '_TEST_ENV', 0);
            $res &= Cfg::updateValue($this->name . '_SHOW_PAYWAY', 1);
            $res &= Cfg::updateValue($this->name . '_GA_TYPE', 2);
            $res &= Cfg::updateValue($this->name . '_GA_TRACKER_ID', 0);
            $res &= Cfg::updateValue($this->name . '_GA4_TRACKER_ID', 0);
            $res &= Cfg::updateValue($this->name . '_GA4_SECRET', 0);
            $res &= Cfg::updateValue($this->name . '_BLIK_REDIRECT', 0);
            $res &= Cfg::updateValue($this->name . '_GPAY_REDIRECT', 0);

            $res &= Cfg::updateValue($this->name . '_PROMO_PAY_LATER', 1);
            $res &= Cfg::updateValue($this->name . '_PROMO_INSTALMENTS', 1);
            $res &= Cfg::updateValue($this->name . '_PROMO_MATCHED_INSTALMENTS', 1);

            $res &= Cfg::updateValue($this->name . '_PROMO_HEADER', 0);
            $res &= Cfg::updateValue($this->name . '_PROMO_FOOTER', 0);
            $res &= Cfg::updateValue($this->name . '_PROMO_LISTING', 0);
            $res &= Cfg::updateValue($this->name . '_PROMO_PRODUCT', 0);
            $res &= Cfg::updateValue($this->name . '_PROMO_CART', 0);
            $res &= Cfg::updateValue($this->name . '_PROMO_CHECKOUT', 1);
        }

        if (!$res) {
            PrestaShopLogger::addLog('Error configuration', 3);
            return false;
        }

        $res &= $this->installConfigurationTranslations();

        $smarty = \Context::getContext()->smarty;
        \Tools::clearAllCache($smarty);
        \Tools::clearCompile($smarty);

        return (bool) $res;
    }


    public function uninstallConfiguration(): bool
    {

        $res = true;

        foreach (Helper::getFields() as $field) {
            $res &= Cfg::deleteByName($field);
        }

        foreach (Helper::getFieldsLang() as $field) {
            $res &= Cfg::deleteByName($field);
        }

        $res &= Cfg::deleteByName($this->name . '_SHARED_KEY');
        $res &= Cfg::deleteByName($this->name . '_SERVICE_PARTNER_ID');

        return (bool) $res;
    }


    public function addOrderStatuses(): bool
    {
        try {
            CustomStatus::addOrderStates(
                \Context::getContext()->language->id,
                $this->module->name_upper
            );
            return true;
        } catch (Exception $exception) {
            \PrestaShopLogger::addLog('BM - Add statuses - error', 4);
            return false;
        }
    }

    public function removeOrderStatuses():bool
    {
        try {
            CustomStatus::removeOrderStates();
            return true;
        } catch (Exception $exception) {
            \PrestaShopLogger::addLog('BM - Remove statuses - error', 4);
            return false;
        }
    }


    /**
     * Install default text translations for fields in the main configuration
     * @return bool
     */
    public function installConfigurationTranslations(): bool
    {
        $res = true;

        $name_lang = [];
        $name_group_lang = [];

        foreach (Language::getLanguages() as $lang) {
            if ($lang['locale'] === "pl-PL") {
                $name_lang[$lang['id_lang']] = $this->translator->trans(
                    'Szybka płatność',
                    [],
                    self::TRANSLATE_GROUP,
                    $lang['locale']
                );
                $name_group_lang[$lang['id_lang']] = $this->translator->trans(
                    'Przelew internetowy',
                    [],
                    self::TRANSLATE_GROUP,
                    $lang['locale']
                );
            } else {
                $name_lang[$lang['id_lang']] = $this->translator->trans(
                    'Fast payment',
                    [],
                    self::TRANSLATE_GROUP,
                    $lang['locale']
                );
                $name_group_lang[$lang['id_lang']] = $this->translator->trans(
                    'Internet transfer',
                    [],
                    self::TRANSLATE_GROUP,
                    $lang['locale']
                );
            }
        }

        if (Shop::isFeatureActive()) {
            foreach (Shop::getContextListShopID() as $shop_id) {
                $group_id = Shop::getGroupFromShop($shop_id, true);

                $res &= Cfg::updateValue($this->name . '_PAYMENT_NAME', $name_lang, $group_id, $shop_id);
                $res &= Cfg::updateValue($this->name . '_PAYMENT_GROUP_NAME', $name_group_lang, $group_id, $shop_id);
            }
        } else {
            $res &= Cfg::updateValue($this->name . '_PAYMENT_NAME', $name_lang);
            $res &= Cfg::updateValue($this->name . '_PAYMENT_GROUP_NAME', $name_group_lang);
        }

        return (bool) $res;
    }
}

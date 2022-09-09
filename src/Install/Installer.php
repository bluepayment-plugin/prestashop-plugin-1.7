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

namespace BluePayment\Install;

use BluePayment\Analyse\Amplitude;
use Db;
use Exception;
use Module;
use Symfony\Component\Translation\TranslatorInterface;
use Tools;
use Tab;
use PrestaShopLogger;
use Language;
use Shop;
use Context;

class Installer
{
    const MODULE_ADMIN_CONTROLLERS = [
        [
            'class_name' => 'AdminBluepaymentPayments',
            'visible' => false,
            'parent' => -1,
            'name' => 'Blue Media - Configuration',
        ],
        [
            'class_name' => 'AdminBluepaymentAjax',
            'visible' => false,
            'parent' => -1,
            'name' => 'Blue Media - Ajax',
        ],
    ];


    const PLUGIN_INSTALLED = 'plugin installed';
    const PLUGIN_UNINSTALLED = 'plugin uninstalled';

    /**
     * @var \BluePayment
     */
    private $module;
    protected $translator;

    public function __construct(\BluePayment $module, TranslatorInterface $translator)
    {
        $this->module = $module;
        $this->translator = $translator;
    }

    /**
     * Installer
     * @throws Exception
     */
    public function install(): bool
    {
        if (version_compare(phpversion(), '7.0.0', '<')) {
            return false;
        }

        $this->installDb();
        $this->installTabs();
        $this->installContext();
        $this->eventInstalled();

        return true;
    }

    /**
     * Uninstall
     * @throws Exception
     */
    public function uninstall(): bool
    {
        $this->uninstallDb();
        $this->uninstallTabs();
        $this->eventUninstalled();

        return true;
    }

    /**
     * Sql data installation
     * @throws Exception
     */
    private function installDb()
    {
        $this->executeSqlFromFile($this->module->getLocalPath() . 'src/Install/install.sql');
    }

    /**
     * Deleting sql data
     * @throws Exception
     */
    private function uninstallDb()
    {
        $this->executeSqlFromFile($this->module->getLocalPath() . 'src/Install/uninstall.sql');
    }

    /**
     * Install tab controller
     */
    public function installTabs(): bool
    {
        $res = true;

        foreach (self::MODULE_ADMIN_CONTROLLERS as $controller) {
            if (Tab::getIdFromClassName($controller['class_name'])) {
                continue;
            }

            $tab = new Tab();
            $tab->class_name = $controller['class_name'];
            $tab->id_parent = $controller['parent'];
            $tab->active = $controller['visible'];

            if (isset($controller['icon'])) {
                $tab->icon = $controller['icon'];
            }

            foreach (Language::getLanguages() as $lang) {
                if ($lang['locale'] === "pl-PL") {
                    $tab->name[$lang['id_lang']] = $this->translator->trans(
                        'Blue Media - Konfiguracja',
                        [],
                        'Modules.Bluepayment.Admin',
                        $lang['locale']
                    );
                } else {
                    $tab->name[$lang['id_lang']] = $this->translator->trans(
                        'Blue Media - Configuration',
                        [],
                        'Modules.Bluepayment.Admin',
                        $lang['locale']
                    );
                }
            }
            $tab->module = $this->module->name;
            $res = $res && $tab->add();
        }

        return $res;
    }


    /**
     * Remove all tabs controller
     */
    public function uninstallTabs(): bool
    {
        foreach (static::MODULE_ADMIN_CONTROLLERS as $controller) {
            $id_tab = (int) \Tab::getIdFromClassName($controller['class_name']);
            $tab = new \Tab($id_tab);
            if (\Validate::isLoadedObject($tab)) {
                $parentTabID = $tab->id_parent;
                $tab->delete();
                $tabCount = \Tab::getNbTabs((int)$parentTabID);
                if ($tabCount == 0) {
                    $parentTab = new \Tab((int)$parentTabID);
                    $parentTab->delete();
                }
            }
        }

        return true;
    }

    /**
     * Execute sql files
     * @param string $path
     * @throws Exception
     */
    private function executeSqlFromFile(string $path)
    {
        $db = Db::getInstance();
        $sqlStatements = Tools::file_get_contents($path);
        $sqlStatements = str_replace(['_DB_PREFIX_', '_MYSQL_ENGINE_'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sqlStatements);

        try {
            $db->execute($sqlStatements);
        } catch (Exception $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }
    }




    public function eventInstalled()
    {
        $data = [
            'events' => [
                "event_type" => self::PLUGIN_INSTALLED,
                "user_properties" => [
                    self::PLUGIN_INSTALLED => true,
                ]
            ],
        ];
        $amplitude = Amplitude::getInstance();
        $amplitude->sendEvent($data);
    }

    public function eventUninstalled()
    {
        $data = [
            'events' => [
                "event_type" => self::PLUGIN_UNINSTALLED,
                "user_properties" => [
                    self::PLUGIN_INSTALLED => false,
                ]
            ],
        ];

        $amplitude = Amplitude::getInstance();
        $amplitude->sendEvent($data);
    }

    public function installContext(): bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_SHOP, Context::getContext()->shop->id);
        }
        return true;
    }
}

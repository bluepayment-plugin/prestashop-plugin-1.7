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

use BlueMedia\ProductFeed\Configuration\FeedConfiguration;
use BlueMedia\ProductFeed\Configuration\XmlDataConfiguration;
use BlueMedia\ProductFeed\DataProvider\ProductDataProvider;
use BlueMedia\ProductFeed\Executor\ProductExecutor;
use BlueMedia\ProductFeed\Generator\XmlGenerator;
use BlueMedia\ProductFeed\Menager\FileMenager;
use BlueMedia\ProductFeed\Presenter\ProductPresenter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BluePaymentFeedModuleFrontController extends ModuleFrontController
{
    /** @var BluePayment */
    public $module;

    public function initContent()
    {
        if (!Configuration::get($this->module->name_upper . FeedConfiguration::AP_SUFFIX_ENABLED_PRODUCT_FEED)) {
            exit('PRODUCT FEED GENERATION IS DISAVLED');
        }

        if (
            Tools::isSubmit('id_lang')
            && Tools::isSubmit('id_shop')
        ) {
            $productProvider = new ProductDataProvider();
            $productPresenter = new ProductPresenter();
            $xmlDataConfiguration = new XmlDataConfiguration($this->module);

            $xmlGenerator = new XmlGenerator($productProvider, $productPresenter, $xmlDataConfiguration);
            $fileMenager = new FileMenager();
            $executor = new ProductExecutor($xmlGenerator, $fileMenager);

            $executor->execute((int) Tools::getValue('id_lang'), (int) Tools::getValue('id_shop'));
        }

        exit('OK');
    }
}

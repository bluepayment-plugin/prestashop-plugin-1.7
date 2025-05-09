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

namespace BlueMedia\ProductFeed\Generator;

use BlueMedia\ProductFeed\Configuration\XmlDataConfiguration;
use BlueMedia\ProductFeed\Configuration\XmlFeedConfiguration;
use BlueMedia\ProductFeed\Creator\SimpleXMLCreator;
use BlueMedia\ProductFeed\DataProvider\ProductDataProvider;
use BlueMedia\ProductFeed\Presenter\ProductPresenter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class XmlGenerator
{
    /**
     * @var ProductDataProvider
     */
    private $productDataProvider;
    /**
     * @var ProductPresenter
     */
    private $productPresenter;
    /**
     * @var XmlDataConfiguration
     */
    private $xmlDataConfiguration;

    public function __construct(
        ProductDataProvider $productDataProvider,
        ProductPresenter $productPresenter,
        XmlDataConfiguration $xmlDataConfiguration
    ) {
        $this->productDataProvider = $productDataProvider;
        $this->productPresenter = $productPresenter;
        $this->xmlDataConfiguration = $xmlDataConfiguration;
    }

    public function productXmlGenerator(int $idLang, int $idShop)
    {
        $products = $this->productDataProvider->getProduct($idLang, $idShop);
        $this->productPresenter->present($products, $idLang, $idShop);

        $namespace = XmlFeedConfiguration::GOOGLE_MERCHANT_XML_NAMESPACE;

        $xml = new SimpleXMLCreator(
            '<?xml version="1.0" encoding="UTF-8" ?><rss xmlns:g="http://base.google.com/ns/1.0" />'
        );
        $channel = $xml->addChild('channel');

        $channel->addChild('title', $this->xmlDataConfiguration->getTitle());
        $channel->addChild('link', $this->xmlDataConfiguration->getShopUrl());
        $channel->addChild('description', $this->xmlDataConfiguration->getDescription());

        foreach ($products as $product) {
            if (
                empty($product['link'])
                || empty($product['title'])
                || empty($product['description'])
                || empty($product['image_link'])
            ) {
                continue;
            }

            $item = $channel->addChild('item');

            $item->addChild('id', $product['id_product'], $namespace);
            $item->addCData('title', $product['name']);
            $item->addCData('link', $product['link']);
            $item->addCData('description', $product['description']);

            if (isset($product['image_link'])) {
                $item->addChild('image_link', $product['image_link'], $namespace);
            }

            $item->addChild('price', $product['price'], $namespace);

            $item->addChild('condition', $product['condition'], $namespace);

            if ($product['quantity'] > 0) {
                $item->addChild('availability', XmlFeedConfiguration::AVAILABILITY_IN_STOCK, $namespace);
            } else {
                $item->addChild('availability', XmlFeedConfiguration::AVAILABILITY_OUT_OF_STOCK, $namespace);
            }

            $item->addChild('mpn', '', $namespace);
            $item->addChild('identifier_exists', 'false', $namespace);
        }

        return $this->generateXmlNoEmptyTag($xml);
    }

    public function generateXmlNoEmptyTag(SimpleXMLCreator $xml): string
    {
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML(null, LIBXML_NOEMPTYTAG);
    }
}

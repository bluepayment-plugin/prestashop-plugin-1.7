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

namespace BlueMedia\ProductFeed\Executor;

use BlueMedia\ProductFeed\Generator\XmlGenerator;
use BlueMedia\ProductFeed\Menager\FileMenager;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductExecutor
{
    /**
     * @var FileMenager
     */
    private $fileMenager;
    /**
     * @var XmlGenerator
     */
    private $xmlGenerator;

    public function __construct(
        XmlGenerator $xmlGenerator,
        FileMenager $fileMenager
    ) {
        $this->fileMenager = $fileMenager;
        $this->xmlGenerator = $xmlGenerator;
    }

    public function execute(int $idLang, int $idShop)
    {
        $xml = $this->xmlGenerator->productXmlGenerator($idLang, $idShop);
        $this->fileMenager->saveData($xml, $idLang, $idShop);
    }
}

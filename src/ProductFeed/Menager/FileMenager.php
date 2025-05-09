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

namespace BlueMedia\ProductFeed\Menager;

use BlueMedia\ProductFeed\Configuration\FileConfiguration;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FileMenager
{
    private $savePath = _PS_ROOT_DIR_;
    private $saveFilename = FileConfiguration::AP_NAME;

    public function saveData(string $xml, int $idLang, int $idShop)
    {
        $generateFilePath = $this->generateFilePath($idLang, $idShop);
        if (file_put_contents($generateFilePath . '_temp', $xml)) { // Flush rest of data
            @unlink($generateFilePath);
            @chmod($generateFilePath . '_temp', 0777);
            rename($generateFilePath . '_temp', $generateFilePath);
            @chmod($generateFilePath, 0777);
        }
    }

    public function removeFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function generateFilePath(int $idLang, int $idShop)
    {
        return $this->getSavePath() . '/' . $this->getSaveFilename() . '-' . $idShop . '-' . $idLang . '.xml';
    }

    /**
     * @return array|false|string
     */
    public function getSavePath()
    {
        return $this->savePath;
    }

    /**
     * @param array|false|string $savePath
     */
    public function setSavePath($savePath): void
    {
        $this->savePath = $savePath;
    }

    public function getSaveFilename(): string
    {
        return $this->saveFilename;
    }

    public function setSaveFilename(string $saveFilename): void
    {
        $this->saveFilename = $saveFilename;
    }
}

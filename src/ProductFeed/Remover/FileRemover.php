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

namespace BlueMedia\ProductFeed\Remover;

use BlueMedia\ProductFeed\Configuration\FileConfiguration;
use BlueMedia\ProductFeed\Menager\FileMenager;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FileRemover
{
    /**
     * @var FileMenager
     */
    private $fileMenager;

    public function __construct(
        FileMenager $fileMenager
    ) {
        $this->fileMenager = $fileMenager;
    }

    public function removeAllFeedFile()
    {
        $pattern = '/' . FileConfiguration::AP_NAME . '-*.xml';
        $files = glob(_PS_ROOT_DIR_ . $pattern);
        foreach ($files as $file) {
            $this->fileMenager->removeFile($file);
        }
    }
}

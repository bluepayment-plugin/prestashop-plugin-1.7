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

namespace BluePayment\Utility\Converter;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Trait to convert objects to arrays for better serialization
 */
trait ObjectToArrayConverter
{
    /**
     * Convert an array of objects to an array of associative arrays
     *
     * @param array $items Array of objects or arrays
     *
     * @return array Converted array
     */
    protected function convertObjectsToArrays(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (is_object($item)) {
                $itemData = [];
                $reflection = new \ReflectionObject($item);

                $properties = $reflection->getProperties();

                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    $key = $property->getName();
                    $value = $property->getValue($item);

                    if (is_object($value) || is_array($value)) {
                        $wasObject = is_object($value);
                        $value = $this->convertObjectsToArrays($wasObject ? [$value] : $value);
                        $value = $wasObject ? reset($value) : $value;
                    }

                    $itemData[$key] = $value;
                }

                $result[] = $itemData;
            } elseif (is_array($item)) {
                $result[] = $this->convertObjectsToArrays($item);
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }
}

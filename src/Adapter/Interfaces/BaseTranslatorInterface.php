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

declare(strict_types=1);

namespace BluePayment\Adapter\Interfaces;

if (!defined('_PS_VERSION_')) {
    exit;
}

// Try to use the newer interface first, fallback to older one
if (interface_exists('Symfony\Contracts\Translation\TranslatorInterface')) {
    class_alias('Symfony\Contracts\Translation\TranslatorInterface', 'BluePayment\Adapter\Interfaces\BaseTranslatorInterface');
} elseif (interface_exists('Symfony\Component\Translation\TranslatorInterface')) {
    class_alias('Symfony\Component\Translation\TranslatorInterface', 'BluePayment\Adapter\Interfaces\BaseTranslatorInterface');
} else {
    // Fallback interface if neither exists
    interface BaseTranslatorInterface
    {
        public function trans($id, array $parameters = [], $domain = null, $locale = null);
    }
}

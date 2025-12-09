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

namespace BluePayment\Adapter;

use BluePayment\Adapter\Interfaces\BaseTranslatorInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adapter for different translator implementations across PrestaShop versions
 * Handles compatibility between PrestaShop 1.7-8.1 and 9.0+
 */
class TranslatorAdapter implements BaseTranslatorInterface
{
    /**
     * @var mixed The actual translator instance
     */
    private $translator;

    /**
     * @var bool Whether we're using the legacy translator interface
     */
    private $isLegacyTranslator;

    public function __construct($translator)
    {
        $this->translator = $translator;

        $this->isLegacyTranslator = false;

        // Check if translator implements any known translator interface
        if (interface_exists('Symfony\Contracts\Translation\TranslatorInterface')
            && $translator instanceof \Symfony\Contracts\Translation\TranslatorInterface) {
            $this->isLegacyTranslator = true;
        } elseif (interface_exists('Symfony\Component\Translation\TranslatorInterface')
            && $translator instanceof \Symfony\Component\Translation\TranslatorInterface) {
            $this->isLegacyTranslator = true;
        } elseif (interface_exists('PrestaShopBundle\Translation\TranslatorInterface')
            && $translator instanceof \PrestaShopBundle\Translation\TranslatorInterface) {
            $this->isLegacyTranslator = true;
        }
    }

    /**
     * Translates the given message.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return string The translated string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        if ($this->isLegacyTranslator) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        if (method_exists($this->translator, 'trans')) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        return (string) $id;
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     */
    public function getLocale(): string
    {
        if (method_exists($this->translator, 'getLocale')) {
            return $this->translator->getLocale();
        }

        if (class_exists('Context') && \Context::getContext() && \Context::getContext()->language) {
            return \Context::getContext()->language->locale;
        }

        return 'en-US';
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     * Required by Symfony\Component\Translation\TranslatorInterface (PS 1.7/8.1)
     *
     * @param string $id The message id
     * @param int $number The number to use to find the index of the message
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return string The translated string
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null): string
    {
        if (method_exists($this->translator, 'transChoice')) {
            return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
        }

        // Fallback to trans() if transChoice() is not available
        return $this->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Sets the current locale.
     * Required by Symfony\Component\Translation\TranslatorInterface (PS 1.7/8.1)
     *
     * @param string $locale The locale
     */
    public function setLocale($locale)
    {
        if (method_exists($this->translator, 'setLocale')) {
            $this->translator->setLocale($locale);
        }
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    /**
     * @var BaseTranslator
     */
    private $translator;
    /**
     * @var array
     */
    private $localDomains = [];

    public function __construct(BaseTranslator $translator, array $localDomains = [])
    {
        $this->translator = $translator;
        $this->localDomains = $localDomains;
    }

    public function trans($id, array $parameters = [], $domain = 'messages', $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        foreach ($this->localDomains as $localDomain) {
            if (false !== $this->hasLocalOverwrite($id, $localDomain, $locale)) {
                $domain = $localDomain;
                break;
            }
        }

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    protected function hasLocalOverwrite($id, $domain, $locale = null): bool
    {
        $found = false;

        $catalogue = $this->getCatalogue($locale);
        while (false === ($found = $catalogue->defines($id, $domain))) {
            if ($cat = $catalogue->getFallbackCatalogue()) {
                $catalogue = $cat;
            } else {
                break;
            }
        }

        return $found;
    }

    /**
     * Gets the catalogue by locale.
     *
     * @param string|null $locale The locale or null to use the default
     *
     * @return MessageCatalogueInterface
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function getCatalogue($locale = null)
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param int $number The number to use to find the index of the message
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }
}

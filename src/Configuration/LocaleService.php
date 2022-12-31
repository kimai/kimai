<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Constants;

final class LocaleService
{
    public function __construct(private array $languageSettings)
    {
    }

    /**
     * Returns an array with all available locale/language codes.
     *
     * @return string[]
     */
    public function getAllLocales(): array
    {
        return array_keys($this->languageSettings);
    }

    public function isKnownLocale(string $language): bool
    {
        return \in_array($language, $this->getAllLocales());
    }

    public function getDefaultLocale(): string
    {
        return Constants::DEFAULT_LOCALE;
    }

    /**
     * Returns the locale specific date format, which should be used in combination with the twig filter "|date".
     *
     * @param string $locale
     * @return string
     */
    public function getDateFormat(string $locale): string
    {
        return $this->getConfig('date', $locale);
    }

    /**
     * Returns the locale specific time format, which should be used in combination with the twig filter "|time".
     *
     * @param string $locale
     * @return string
     */
    public function getTimeFormat(string $locale): string
    {
        return $this->getConfig('time', $locale);
    }

    /**
     * Returns the locale specific datetime format, which should be used in combination with the twig filter "|date".
     *
     * @param string $locale
     * @return string
     */
    public function getDateTimeFormat(string $locale): string
    {
        return $this->getDateFormat($locale) . ' ' . $this->getTimeFormat($locale);
    }

    /**
     * Returns the format used in the "|duration" twig filter to display a Timesheet duration.
     *
     * @param string $locale
     * @return string
     */
    public function getDurationFormat(string $locale): string
    {
        return '%h:%m';
    }

    public function isRightToLeft(string $locale): bool
    {
        return $this->getConfig('rtl', $locale);
    }

    public function is24Hour(string $locale): bool
    {
        $format = $this->getTimeFormat($locale);

        return stripos($format, 'a') === false;
    }

    private function getConfig(string $key, string $locale): string|bool
    {
        if (!isset($this->languageSettings[$locale])) {
            throw new \InvalidArgumentException(sprintf('Unknown locale given: %s', $locale));
        }

        if (!isset($this->languageSettings[$locale][$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown setting for locale %s: %s', $locale, $key));
        }

        return $this->languageSettings[$locale][$key];
    }
}

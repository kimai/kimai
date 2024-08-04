<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

use App\Entity\User;

final class LocaleService
{
    public const DEFAULT_SETTINGS = [
        'date' => 'dd.MM.y',
        'time' => 'HH:mm',
        'rtl' => false,
        'translation' => false,
    ];

    /**
     * @param array<string, array{'date': string, 'time': string, 'translation': bool}> $languageSettings
     */
    public function __construct(private readonly array $languageSettings)
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

    /**
     * Returns an array with all language codes that have translations.
     *
     * @return string[]
     */
    public function getTranslatedLocales(): array
    {
        return array_keys(array_filter($this->languageSettings, function (array $setting) {
            return $setting['translation'];
        }));
    }

    public function isKnownLocale(string $language): bool
    {
        return \in_array($language, $this->getAllLocales(), true);
    }

    /**
     * Returns the locale specific date format, which should be used in combination with the twig filter "|date".
     *
     * @param string $locale
     * @return string
     */
    public function getDateFormat(string $locale): string
    {
        return (string) $this->getConfig('date', $locale);
    }

    /**
     * Returns the locale specific time format, which should be used in combination with the twig filter "|time".
     *
     * @param string $locale
     * @return string
     */
    public function getTimeFormat(string $locale): string
    {
        return (string) $this->getConfig('time', $locale);
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
        return (bool) $this->getConfig('rtl', $locale);
    }

    public function isTranslated(string $locale): bool
    {
        return (bool) $this->getConfig('translation', $locale);
    }

    public function getNearestTranslationLocale(string $locale): string
    {
        if (!$this->isKnownLocale($locale)) {
            $parts = explode('_', $locale);
            if (\count($parts) !== 2 || \strlen($parts[0]) !== 2 || !$this->isKnownLocale($parts[0])) {
                return User::DEFAULT_LANGUAGE;
            }
            $locale = $parts[0];
        }

        if (!$this->isTranslated($locale)) {
            $base = explode('_', $locale)[0];
            if (!$this->isTranslated($base)) {
                return User::DEFAULT_LANGUAGE;
            }

            return $base;
        }

        return $locale;
    }

    public function is24Hour(string $locale): bool
    {
        $format = $this->getTimeFormat($locale);

        return stripos($format, 'a') === false;
    }

    private function getConfig(string $key, string $locale): string|bool
    {
        if (!isset($this->languageSettings[$locale])) {
            throw new \InvalidArgumentException(\sprintf('Unknown locale given: %s', $locale));
        }

        if (!isset($this->languageSettings[$locale][$key])) {
            throw new \InvalidArgumentException(\sprintf('Unknown setting for locale %s: %s', $locale, $key));
        }

        return $this->languageSettings[$locale][$key];
    }
}

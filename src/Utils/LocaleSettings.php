<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\HttpFoundation\RequestStack;

class LocaleSettings
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $locale = 'en';

    /**
     * @param RequestStack $requestStack
     * @param array $languageSettings
     */
    public function __construct(RequestStack $requestStack, array $languageSettings)
    {
        // It can be null in a console command
        if (null !== $requestStack->getMasterRequest()) {
            $this->locale = $requestStack->getMasterRequest()->getLocale();
        }
        $this->settings = $languageSettings;
    }

    /**
     * Returns an array with all available locale/language codes.
     *
     * @return string[]
     */
    public function getAvailableLanguages(): array
    {
        return array_keys($this->settings);
    }

    /**
     * Returns the current locale used by the user in this request.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Returns the format which is used by the form component to handle date values.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDateTypeFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date_type', $locale);
    }

    /**
     * Returns the format which is used by the Javascript component to handle date values.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDatePickerFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date_picker', $locale);
    }

    /**
     * Returns the format which is used by the form component to handle datetime values.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDateTimeTypeFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date_time_type', $locale);
    }

    /**
     * Returns the format which is used by the Javascript component to handle datetime values.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDateTimePickerFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date_time_picker', $locale);
    }

    /**
     * Returns the locale specific date format, which should be used in combination with the twig filter "|date".
     *
     * @param null|string $locale
     * @return string
     */
    public function getDateFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date', $locale);
    }

    /**
     * Returns the locale specific datetime format, which should be used in combination with the twig filter "|date".
     *
     * @param null|string $locale
     * @return string
     */
    public function getDateTimeFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('date_time', $locale);
    }

    /**
     * Returns the format used in the "|duration" twig filter to display a Timesheet duration.
     *
     * @param null|string $locale
     * @return string
     */
    public function getDurationFormat(?string $locale = null): string
    {
        return $this->getConfigByLocaleAndKey('duration', $locale);
    }

    /**
     * @param string $key
     * @param null|string $locale
     * @return string
     */
    protected function getConfigByLocaleAndKey(string $key, ?string $locale = null): string
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (!isset($this->settings[$locale])) {
            throw new \InvalidArgumentException(sprintf('Unknown locale given: %s', $locale));
        }

        if (!isset($this->settings[$locale][$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown setting for locale %s: %s', $locale, $key));
        }

        return $this->settings[$locale][$key];
    }
}

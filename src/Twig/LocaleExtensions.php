<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\LanguageFormattings;
use App\Constants;
use App\Entity\Timesheet;
use App\Utils\Duration;
use App\Utils\LocaleFormats;
use App\Utils\LocaleHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Locale specific Twig extensions
 */
final class LocaleExtensions extends AbstractExtension
{
    /**
     * @var LocaleFormats
     */
    private $localeFormats;
    /**
     * @var Duration
     */
    private $durationFormatter;
    /**
     * @var LocaleHelper
     */
    private $helper;
    /**
     * @var LanguageFormattings
     */
    private $formats;

    public function __construct(RequestStack $requestStack, LanguageFormattings $formats)
    {
        $locale = Constants::DEFAULT_LOCALE;

        // request is null in a console command
        if (null !== $requestStack->getMasterRequest()) {
            $locale = $requestStack->getMasterRequest()->getLocale();
        }

        $this->durationFormatter = new Duration();
        $this->formats = $formats;
        $this->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('duration', [$this, 'duration']),
            new TwigFilter('duration_decimal', [$this, 'durationDecimal']),
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('currency', [$this, 'currency']),
            new TwigFilter('country', [$this, 'country']),
            new TwigFilter('language', [$this, 'language']),
            new TwigFilter('amount', [$this, 'amount']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('locales', [$this, 'getLocales']),
        ];
    }

    /**
     * Allows to switch the locale used for all twig filter and functions.
     *
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        $this->helper = new LocaleHelper($locale);
        $this->localeFormats = new LocaleFormats($this->formats, $locale);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet $duration
     * @param bool $decimal
     * @return string
     */
    public function duration($duration, $decimal = false)
    {
        if ($decimal) {
            return $this->durationDecimal($duration);
        }

        $seconds = $this->getSecondsForDuration($duration);
        $format = $this->localeFormats->getDurationFormat();

        return $this->formatDuration($seconds, $format);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet $duration
     * @return string
     */
    public function durationDecimal($duration)
    {
        $seconds = $this->getSecondsForDuration($duration);

        return $this->helper->durationDecimal($seconds);
    }

    private function getSecondsForDuration($duration): int
    {
        if (null === $duration) {
            $duration = 0;
        }

        if ($duration instanceof Timesheet) {
            if (null === $duration->getEnd()) {
                $duration = time() - $duration->getBegin()->getTimestamp();
            } else {
                $duration = $duration->getDuration();
            }
        }

        return (int) $duration;
    }

    private function formatDuration(int $seconds, string $format): string
    {
        if ($seconds < 0) {
            return '?';
        }

        return $this->durationFormatter->format($seconds, $format);
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->helper->amount($amount);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        return $this->helper->currency($currency);
    }

    /**
     * @param string $language
     * @return string
     */
    public function language($language)
    {
        return $this->helper->language($language);
    }

    /**
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        return $this->helper->country($country);
    }

    /**
     * @param float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function money($amount, ?string $currency = null, bool $withCurrency = true)
    {
        return $this->helper->money($amount, $currency, $withCurrency);
    }

    /**
     * Takes the list of codes of the locales (languages) enabled in the
     * application and returns an array with the name of each locale written
     * in its own language (e.g. English, Français, Español, etc.)
     *
     * @return array
     */
    public function getLocales()
    {
        $locales = [];
        foreach ($this->localeFormats->getAvailableLanguages() as $locale) {
            $locales[] = ['code' => $locale, 'name' => Locales::getName($locale, $locale)];
        }

        return $locales;
    }
}

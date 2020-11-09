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
use App\Utils\LocaleFormatter;
use Symfony\Component\HttpFoundation\RequestStack;
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
     * @var LocaleFormatter
     */
    private $formatter;
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
        $this->formatter = new LocaleFormatter($this->formats, $locale);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet|null $duration
     * @param bool $decimal
     * @return string
     */
    public function duration($duration, $decimal = false)
    {
        return $this->formatter->duration($duration, $decimal);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet|null $duration
     * @return string
     */
    public function durationDecimal($duration)
    {
        return $this->formatter->durationDecimal($duration);
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->formatter->amount($amount);
    }

    /**
     * Returns the currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        return $this->formatter->currency($currency);
    }

    /**
     * @param string $language
     * @return string
     */
    public function language($language)
    {
        return $this->formatter->language($language);
    }

    /**
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        return $this->formatter->country($country);
    }

    /**
     * @param float $amount
     * @param string|null $currency
     * @param bool $withCurrency
     * @return string
     */
    public function money($amount, ?string $currency = null, bool $withCurrency = true)
    {
        return $this->formatter->money($amount, $currency, $withCurrency);
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
        return $this->formatter->getLocales();
    }
}

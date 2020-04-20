<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Constants;
use App\Entity\Timesheet;
use App\Utils\Duration;
use App\Utils\LocaleSettings;
use NumberFormatter;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends AbstractExtension
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var Duration
     */
    protected $durationFormatter;
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;
    /**
     * @var NumberFormatter
     */
    protected $moneyFormatter;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
        $this->durationFormatter = new Duration();
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
            new TwigFilter('docu_link', [$this, 'documentationLink']),
            new TwigFilter('multiline_indent', [$this, 'multilineIndent']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('locales', [$this, 'getLocales']),
            new TwigFunction('class_name', [$this, 'getClassName']),
        ];
    }

    /**
     * @param object $object
     * @return null|string
     */
    public function getClassName($object)
    {
        if (!\is_object($object)) {
            return null;
        }

        return \get_class($object);
    }

    public function multilineIndent(?string $string, string $indent): string
    {
        if (null === $string || '' === $string) {
            return '';
        }

        $parts = explode("\r\n", $string);
        if (\count($parts) === 1) {
            $parts = explode("\n", $string);
        }

        $parts = array_map(function ($part) use ($indent) {
            return $indent . $part;
        }, $parts);

        return implode("\n", $parts);
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

        $duration = $this->getSecondsForDuration($duration);
        $format = $this->localeSettings->getDurationFormat();

        return $this->formatDuration($duration, $format);
    }

    /**
     * Transforms seconds into a decimal formatted duration string.
     *
     * @param int|Timesheet $duration
     * @return string
     */
    public function durationDecimal($duration)
    {
        $duration = $this->getSecondsForDuration($duration);

        return $this->getNumberFormatter()->format(number_format($duration / 3600, 2));
    }

    /**
     * @param string|float $amount
     * @return bool|false|string
     */
    public function amount($amount)
    {
        return $this->getNumberFormatter()->format($amount);
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

    protected function formatDuration(int $seconds, string $format): string
    {
        if ($seconds < 0) {
            return '?';
        }

        return $this->durationFormatter->format($seconds, $format);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        return Currencies::getSymbol($currency);
    }

    /**
     * @param string $language
     * @return string
     */
    public function language($language)
    {
        return Languages::getName($language, $this->locale);
    }

    /**
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        $country = strtoupper($country);
        if (Countries::exists($country)) {
            return Countries::getName($country);
        }

        return $country;
    }

    /**
     * @param string $url
     * @return string
     */
    public function documentationLink($url = '')
    {
        return Constants::HOMEPAGE . '/documentation/' . $url;
    }

    private function initLocale()
    {
        $locale = $this->localeSettings->getLocale();

        if ($this->locale === $locale) {
            return;
        }

        $this->locale = $locale;
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $this->moneyFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    }

    private function getNumberFormatter(): NumberFormatter
    {
        $this->initLocale();

        return $this->numberFormatter;
    }

    private function getMoneyFormatter(): NumberFormatter
    {
        $this->initLocale();

        return $this->moneyFormatter;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function money($amount, $currency = null)
    {
        if (null !== $currency) {
            return $this->getMoneyFormatter()->formatCurrency($amount, $currency);
        }

        return $this->getNumberFormatter()->format($amount);
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
        foreach ($this->localeSettings->getAvailableLanguages() as $locale) {
            $locales[] = ['code' => $locale, 'name' => Locales::getName($locale, $locale)];
        }

        return $locales;
    }
}

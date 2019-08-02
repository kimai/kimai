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
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('currency', [$this, 'currency']),
            new TwigFilter('country', [$this, 'country']),
            new TwigFilter('docu_link', [$this, 'documentationLink']),
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
        if (!is_object($object)) {
            return null;
        }

        return get_class($object);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet $duration
     * @param string $format
     * @return string
     */
    public function duration($duration, $format = null)
    {
        if (null === $duration) {
            $duration = 0;
        }

        if ($duration instanceof Timesheet) {
            $seconds = $duration->getDuration();
            if (null === $duration->getEnd()) {
                $seconds = time() - $duration->getBegin()->getTimestamp();
            }

            $duration = $seconds;
        }

        return $this->formatDuration((int) $duration, $format);
    }

    protected function formatDuration(int $seconds, $format = null): string
    {
        if ($seconds < 0) {
            return '?';
        }

        if (null === $format) {
            $format = $this->localeSettings->getDurationFormat();
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
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        return Countries::getName($country);
    }

    /**
     * @param string $url
     * @return string
     */
    public function documentationLink($url = '')
    {
        return Constants::HOMEPAGE . '/documentation/' . $url;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function money($amount, $currency = null)
    {
        $locale = $this->localeSettings->getLocale();

        if ($this->locale !== $locale) {
            $this->locale = $locale;
            $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $this->moneyFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        }

        if (null !== $currency) {
            return $this->moneyFormatter->formatCurrency($amount, $currency);
        }

        return $this->numberFormatter->format($amount);
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

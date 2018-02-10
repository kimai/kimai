<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\Duration;
use Symfony\Component\Intl\Intl;
use App\Entity\Timesheet;
use Twig\TwigFilter;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends \Twig_Extension
{
    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var Duration
     */
    protected $durationFormatter;

    /**
     * Extensions constructor.
     * @param string $locales
     */
    public function __construct($locales)
    {
        $this->locales = explode('|', $locales);
        $this->durationFormatter = new Duration();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('duration', [$this, 'duration']),
            new TwigFilter('durationForEntry', [$this, 'durationForEntry']),
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('currency', [$this, 'currency']),
            new TwigFilter('country', [$this, 'country']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('locales', [$this, 'getLocales']),
        ];
    }

    /**
     * Returns the formatted duration for a Timesheet entry.
     *
     * @param Timesheet $entry
     * @param bool $includeSeconds
     * @return string
     */
    public function durationForEntry(Timesheet $entry, $includeSeconds = false)
    {
        return $this->duration($entry->getDuration(), $includeSeconds);
    }

    /**
     * Transforms seconds into a duration string.
     *
     * @param $seconds
     * @param bool $includeSeconds
     * @return string
     */
    public function duration($seconds, $includeSeconds = false)
    {
        return $this->durationFormatter->format($seconds, $includeSeconds) . ' h';
    }

    /**
     * @param string $currency
     * @return string
     */
    public function currency($currency)
    {
        return Intl::getCurrencyBundle()->getCurrencySymbol($currency);
    }

    /**
     * @param string $country
     * @return string
     */
    public function country($country)
    {
        return Intl::getRegionBundle()->getCountryName($country);
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function money($amount, $currency = null)
    {
        $result = number_format(round($amount, 2), 2);
        if ($currency !== null) {
            $result .= ' ' . Intl::getCurrencyBundle()->getCurrencySymbol($currency);
        }
        return $result;
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
        foreach ($this->locales as $locale) {
            $locales[] = ['code' => $locale, 'name' => Intl::getLocaleBundle()->getLocaleName($locale, $locale)];
        }

        return $locales;
    }
}

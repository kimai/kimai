<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\Timesheet;
use App\Utils\Duration;
use NumberFormatter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Intl;
use Twig\TwigFilter;

/**
 * Multiple Twig extensions: filters and functions
 */
class Extensions extends \Twig_Extension
{
    /**
     * @var string[]
     */
    protected $locales;

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
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * Extensions constructor.
     * @param string $locales
     * @param string $locale
     */
    public function __construct(RequestStack $requestStack, $locales)
    {
        $this->requestStack = $requestStack;
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
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('currency', [$this, 'currency']),
            new TwigFilter('country', [$this, 'country']),
            new TwigFilter('icon', [$this, 'icon']),
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
     * Transforms seconds into a duration string.
     *
     * @param int|Timesheet $duration
     * @param bool $includeSeconds
     * @return string
     */
    public function duration($duration, $includeSeconds = false)
    {
        $seconds = $duration;
        if ($duration instanceof Timesheet) {
            $seconds = $duration->getDuration();
            if (null === $duration->getEnd()) {
                $seconds = time() - $duration->getBegin()->getTimestamp();
            }
        }

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
     * @param string $name
     * @param string $default
     * @return string
     */
    public function icon($name, $default = '')
    {
        $icons = [
            'user' => 'fas fa-user',
            'customer' => 'fas fa-users',
            'project' => 'fas fa-project-diagram',
            'activity' => 'fas fa-tasks',
            'admin' => 'fas fa-wrench',
            'invoice' => 'fas fa-file-invoice',
            'timesheet' => 'far fa-clock',
            'dashboard' => 'fas fa-tachometer-alt',
            'logout' => 'fas fa-sign-out-alt',
            'trash' => 'far fa-trash-alt',
            'delete' => 'far fa-trash-alt',
            'repeat' => 'fas fa-redo-alt',
            'edit' => 'far fa-edit',
            'manual' => 'fas fa-book',
            'help' => 'far fa-question-circle',
            'start' => 'fas fa-play-circle',
            'start-small' => 'fas fa-play-circle',
            'stop' => 'fas fa-stop',
            'stop-small' => 'far fa-stop-circle',
            'filter' => 'fas fa-filter',
            'create' => 'far fa-plus-square',
            'list' => 'fas fa-list',
            'print' => 'fas fa-print',
        ];

        return $icons[$name] ?? $default;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function money($amount, $currency = null)
    {
        $locale = $this->getLocale();

        if ($this->locale !== $locale) {
            $this->locale = $locale;
            $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        }

        $fractionDigits = Intl::getCurrencyBundle()->getFractionDigits($currency);
        $amount = round($amount, $fractionDigits);
        $result = $this->numberFormatter->format($amount);

        if (null !== $currency) {
            $result .= ' ' . Intl::getCurrencyBundle()->getCurrencySymbol($currency, $locale);
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->requestStack->getCurrentRequest()->getLocale();
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

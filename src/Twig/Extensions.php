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
     * @var array
     */
    protected $cookies = [];

    /**
     * @var string[]
     */
    protected static $icons = [
        'activity' => 'fas fa-tasks',
        'admin' => 'fas fa-wrench',
        'calendar' => 'far fa-calendar-alt',
        'customer' => 'fas fa-users',
        'copy' => 'far fa-copy',
        'create' => 'far fa-plus-square',
        'dashboard' => 'fas fa-tachometer-alt',
        'delete' => 'far fa-trash-alt',
        'download' => 'fas fa-download',
        'duration' => 'far fa-hourglass',
        'edit' => 'far fa-edit',
        'filter' => 'fas fa-filter',
        'help' => 'far fa-question-circle',
        'invoice' => 'fas fa-file-invoice',
        'list' => 'fas fa-list',
        'logout' => 'fas fa-sign-out-alt',
        'manual' => 'fas fa-book',
        'money' => 'far fa-money-bill-alt',
        'print' => 'fas fa-print',
        'project' => 'fas fa-project-diagram',
        'repeat' => 'fas fa-redo-alt',
        'start' => 'fas fa-play-circle',
        'start-small' => 'fas fa-play-circle',
        'stop' => 'fas fa-stop',
        'stop-small' => 'far fa-stop-circle',
        'timesheet' => 'far fa-clock',
        'trash' => 'far fa-trash-alt',
        'user' => 'fas fa-user',
        'visibility' => 'far fa-eye',
        'settings' => 'fas fa-wrench',
        'export' => 'fas fa-database',
        'pdf' => 'fas fa-file-pdf',
        'csv' => 'fas fa-table',
        'ods' => 'fas fa-table',
        'xlsx' => 'fas fa-file-excel',
        'on' => 'fas fa-toggle-on',
        'off' => 'fas fa-toggle-off',
    ];

    /**
     * @param RequestStack $requestStack
     * @param array $languages
     */
    public function __construct(RequestStack $requestStack, array $languages)
    {
        $this->requestStack = $requestStack;
        $this->locales = $languages;
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
            new \Twig_SimpleFunction('is_visible_column', [$this, 'isColumnVisible']),
            new \Twig_SimpleFunction('is_datatable_configured', [$this, 'isDatatableConfigured']),
        ];
    }

    /**
     * @param string $dataTable
     * @param string $size
     * @return bool
     */
    public function isDatatableConfigured(string $dataTable, string $size)
    {
        $cookie = $this->getVisibilityCookieName($dataTable, $size);

        return $this->requestStack->getCurrentRequest()->cookies->has($cookie);
    }

    /**
     * @param string $dataTable
     * @param string $size
     * @return string
     */
    public function getVisibilityCookieName(string $dataTable, string $size)
    {
        return $dataTable . '_visibility' . $size;
    }

    /**
     * This is only for datatables, do not use it outside this context.
     *
     * @param string $dataTable
     * @param string $column
     * @param string $size
     * @return bool
     */
    public function isColumnVisible(string $dataTable, string $column, string $size)
    {
        // name handling is spread between here and datatables.html.twig (data_table_column_modal)
        $cookie = $this->getVisibilityCookieName($dataTable, $size);

        if (!isset($this->cookies[$cookie])) {
            $visibility = false;
            if ($this->requestStack->getCurrentRequest()->cookies->has($cookie)) {
                $visibility = json_decode($this->requestStack->getCurrentRequest()->cookies->get($cookie), true);
            }
            $this->cookies[$cookie] = $visibility;
        }
        $values = $this->cookies[$cookie];

        if (empty($values) || !is_array($values)) {
            return true;
        }

        if (isset($values[$column]) && $values[$column] === false) {
            return false;
        }

        return true;
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
        $seconds = $duration;
        if ($duration instanceof Timesheet) {
            $seconds = $duration->getDuration();
            if (null === $duration->getEnd()) {
                $seconds = time() - $duration->getBegin()->getTimestamp();
            }
        }

        if ($seconds < 0) {
            return '?';
        }

        $locale = $this->getLocale();
        switch ($format) {
            case 'full':
                $format = isset($this->locales[$locale]) ? $this->locales[$locale]['duration'] : null;
                break;
            case null:
            case 'short':
                $format = isset($this->locales[$locale]) ? $this->locales[$locale]['duration_short'] : null;
                break;
        }

        if (null === $format) {
            $format = '%h:%m h';
        }

        return $this->durationFormatter->format($seconds, $format);
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
        return self::$icons[$name] ?? $default;
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
        foreach (array_keys($this->locales) as $locale) {
            $locales[] = ['code' => $locale, 'name' => Intl::getLocaleBundle()->getLocaleName($locale, $locale)];
        }

        return $locales;
    }
}

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
use Symfony\Component\Intl\Intl;
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
        'start-small' => 'far fa-play-circle',
        'stop' => 'fas fa-stop',
        'stop-small' => 'far fa-stop-circle',
        'timesheet' => 'fas fa-clock',
        'trash' => 'far fa-trash-alt',
        'user' => 'fas fa-user',
        'visibility' => 'far fa-eye',
        'settings' => 'fas fa-cog',
        'export' => 'fas fa-file-export',
        'pdf' => 'fas fa-file-pdf',
        'csv' => 'fas fa-table',
        'ods' => 'fas fa-table',
        'xlsx' => 'fas fa-file-excel',
        'on' => 'fas fa-toggle-on',
        'off' => 'fas fa-toggle-off',
        'audit' => 'fas fa-history',
        'home' => 'fas fa-home',
        'shop' => 'fas fa-shopping-cart',
        'about' => 'fas fa-info-circle',
        'debug' => 'far fa-file-alt',
        'profile-stats' => 'far fa-chart-bar',
        'profile' => 'fas fa-user-edit',
        'warning' => 'fas fa-exclamation-triangle',
        'permissions' => 'fas fa-user-lock',
        'back' => 'fas fa-long-arrow-alt-left',
    ];

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
            new TwigFilter('icon', [$this, 'icon']),
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
            return null;
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
            $locales[] = ['code' => $locale, 'name' => Intl::getLocaleBundle()->getLocaleName($locale, $locale)];
        }

        return $locales;
    }
}

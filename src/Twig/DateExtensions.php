<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Utils\LocaleSettings;
use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Date specific twig extensions
 */
class DateExtensions extends AbstractExtension
{
    /**
     * @var LocaleSettings|null
     */
    protected $localeSettings = null;
    /**
     * @var string
     */
    protected $dateFormat = null;
    /**
     * @var string
     */
    protected $dateTimeFormat = null;
    /**
     * @var string
     */
    protected $dateTimeTypeFormat = null;
    /**
     * @var string
     */
    protected $timeFormat = null;
    /**
     * @var bool
     */
    protected $isTwentyFourHour = null;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('month_name', [$this, 'monthName']),
            new TwigFilter('date_short', [$this, 'dateShort']),
            new TwigFilter('date_time', [$this, 'dateTime']),
            new TwigFilter('date_full', [$this, 'dateTimeFull']),
            new TwigFilter('date_format', [$this, 'dateFormat']),
            new TwigFilter('time', [$this, 'time']),
            new TwigFilter('hour24', [$this, 'hour24']),
        ];
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateShort(DateTime $date)
    {
        if (null === $this->dateFormat) {
            $this->dateFormat = $this->localeSettings->getDateFormat();
        }

        return date_format($date, $this->dateFormat);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateTime(DateTime $date)
    {
        if (null === $this->dateTimeFormat) {
            $this->dateTimeFormat = $this->localeSettings->getDateTimeFormat();
        }

        return date_format($date, $this->dateTimeFormat);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function dateTimeFull(DateTime $date)
    {
        if (null === $this->dateTimeTypeFormat) {
            $this->dateTimeTypeFormat = $this->localeSettings->getDateTimeTypeFormat();
        }

        $formatter = new \IntlDateFormatter(
            $this->localeSettings->getLocale(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN,
            $this->dateTimeTypeFormat
        );

        return $formatter->format($date);
    }

    /**
     * @param DateTime $date
     * @param string $format
     * @return false|string
     */
    public function dateFormat(DateTime $date, string $format)
    {
        return date_format($date, $format);
    }

    /**
     * @param DateTime $date
     * @return string
     */
    public function time(DateTime $date)
    {
        if (null === $this->timeFormat) {
            $this->timeFormat = $this->localeSettings->getTimeFormat();
        }

        return date_format($date, $this->timeFormat);
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function monthName(\DateTime $date)
    {
        return 'month.' . $date->format('n');
    }

    /**
     * @param mixed $twentyFour
     * @param mixed $twelveHour
     * @return mixed
     */
    public function hour24($twentyFour, $twelveHour)
    {
        if (null === $this->isTwentyFourHour) {
            $this->isTwentyFourHour = $this->localeSettings->isTwentyFourHours();
        }

        if (true === $this->isTwentyFourHour) {
            return $twentyFour;
        }

        return $twelveHour;
    }
}

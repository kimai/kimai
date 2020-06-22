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
use Twig\TwigFunction;

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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_format_duration', [$this, 'getDurationFormat']),
        ];
    }

    /**
     * @param DateTime|string $date
     * @return string
     * @throws \Exception
     */
    public function dateShort($date)
    {
        if (null === $this->dateFormat) {
            $this->dateFormat = $this->localeSettings->getDateFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (\Exception $ex) {
                return $date;
            }
        }

        return date_format($date, $this->dateFormat);
    }

    /**
     * @param DateTime|string $date
     * @return string
     * @throws \Exception
     */
    public function dateTime($date)
    {
        if (null === $this->dateTimeFormat) {
            $this->dateTimeFormat = $this->localeSettings->getDateTimeFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (\Exception $ex) {
                return $date;
            }
        }

        return $date->format($this->dateTimeFormat);
    }

    /**
     * @param DateTime|string $date
     * @param bool $userTimezone
     * @return bool|false|string
     * @throws \Exception
     */
    public function dateTimeFull($date, bool $userTimezone = true)
    {
        if (null === $this->dateTimeTypeFormat) {
            $this->dateTimeTypeFormat = $this->localeSettings->getDateTimeTypeFormat();
        }

        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (\Exception $ex) {
                return $date;
            }
        }

        $timezone = date_default_timezone_get();

        if (!$userTimezone) {
            $timezone = $date->getTimezone()->getName();
        }

        $formatter = new \IntlDateFormatter(
            $this->localeSettings->getLocale(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM,
            $timezone,
            \IntlDateFormatter::GREGORIAN,
            $this->dateTimeTypeFormat
        );

        return $formatter->format($date);
    }

    /**
     * @param DateTime|string $date
     * @param string $format
     * @return false|string
     * @throws \Exception
     */
    public function dateFormat($date, string $format)
    {
        if (!$date instanceof DateTime) {
            try {
                $date = new DateTime($date);
            } catch (\Exception $ex) {
                return $date;
            }
        }

        return date_format($date, $format);
    }

    /**
     * @param DateTime|string $date
     * @return string
     * @throws \Exception
     */
    public function time($date)
    {
        if (null === $this->timeFormat) {
            $this->timeFormat = $this->localeSettings->getTimeFormat();
        }

        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
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

    /**
     * @return string
     */
    public function getDurationFormat()
    {
        return $this->localeSettings->getDurationFormat();
    }
}

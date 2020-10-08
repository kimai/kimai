<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use DateTime;
use DateTimeZone;

class DateTimeFactory
{
    /**
     * @var DateTimeZone
     */
    private $timezone;

    public function __construct(?DateTimeZone $timezone = null)
    {
        if (null === $timezone) {
            $timezone = new \DateTimeZone(date_default_timezone_get());
        }
        $this->setTimezone($timezone);
    }

    public function setTimezone(DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function getStartOfMonth(): DateTime
    {
        $date = $this->createDateTime('first day of this month');
        $date->setTime(0, 0, 0);

        return $date;
    }

    public function getStartOfWeek(?DateTime $date = null): DateTime
    {
        if (null === $date) {
            $date = $this->createDateTime('now');
        }

        return $this->createWeekDateTime($date->format('Y'), $date->format('W'), 1, 0, 0, 0);
    }

    public function getEndOfWeek(?DateTime $date = null): DateTime
    {
        if (null === $date) {
            $date = $this->createDateTime('now');
        }

        return $this->createWeekDateTime($date->format('Y'), $date->format('W'), 7, 23, 59, 59);
    }

    public function getEndOfMonth(): DateTime
    {
        $date = $this->createDateTime('last day of this month');
        $date->setTime(23, 59, 59);

        return $date;
    }

    private function createWeekDateTime($year, $week, $day, $hour, $minute, $second)
    {
        $date = new DateTime('now', $this->getTimezone());
        $date->setISODate($year, $week, $day);
        $date->setTime($hour, $minute, $second);

        return $date;
    }

    public function createDateTime(string $datetime = 'now'): DateTime
    {
        $date = new DateTime($datetime, $this->getTimezone());

        return $date;
    }

    /**
     * @param string $format
     * @param null|string $datetime
     * @return bool|DateTime
     */
    public function createDateTimeFromFormat(string $format, ?string $datetime = 'now')
    {
        $date = DateTime::createFromFormat($format, $datetime, $this->getTimezone());

        return $date;
    }
}

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

    public function getStartOfWeek(?DateTime $date = null): DateTime
    {
        if (null === $date) {
            $date = $this->createDateTime('now');
        }

        return $this->createWeekDateTime($date->format('o'), $date->format('W'), 1, 0, 0, 0);
    }

    public function getStartOfMonth(): DateTime
    {
        $date = $this->createDateTime('first day of this month');
        $date->setTime(0, 0, 0);

        return $date;
    }

    public function getStartOfYear(): DateTime
    {
        $date = $this->createDateTime('first day of january this year');
        $date->setTime(0, 0, 0);

        return $date;
    }

    public function getStartOfDecade(): DateTime
    {
        $date = $this->createDateTime('first day of january this year');
        $decade = (int) (floor((int) $date->format('Y') / 10) * 10);

        $date->setDate($decade, 1, 1);
        $date->setTime(0, 0, 0);

        return $date;
    }

    public function getEndOfWeek(?DateTime $date = null): DateTime
    {
        if (null === $date) {
            $date = $this->createDateTime('now');
        }

        return $this->createWeekDateTime($date->format('o'), $date->format('W'), 7, 23, 59, 59);
    }

    public function getEndOfMonth(): DateTime
    {
        $date = $this->createDateTime('last day of this month');
        $date->setTime(23, 59, 59);

        return $date;
    }

    public function getEndOfYear(): DateTime
    {
        $date = $this->createDateTime('last day of december this year');
        $date->setTime(23, 59, 59);

        return $date;
    }

    public function getEndOfDecade(): DateTime
    {
        // Create DateTime for first second of next decade
        $date = $this->createDateTime('first day of january this year');
        $nextDecade = (int) (ceil((int) $date->format('Y') / 10) * 10);

        $date->setDate($nextDecade, 1, 1);
        $date->setTime(0, 0, 0);

        // Subtract a second to jump back to last second of current decade
        $date->modify('-1 second');

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

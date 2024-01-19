<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\User;
use DateTime;
use DateTimeInterface;
use DateTimeZone;

final class DateTimeFactory
{
    private DateTimeZone $timezone;
    private bool $startOnSunday = false;

    public static function createByUser(User $user): self
    {
        return new DateTimeFactory(new \DateTimeZone($user->getTimezone()), $user->isFirstDayOfWeekSunday());
    }

    public function __construct(?DateTimeZone $timezone = null, bool $startOnSunday = false)
    {
        if (null === $timezone) {
            $timezone = new \DateTimeZone(date_default_timezone_get());
        }
        $this->timezone = $timezone;
        $this->startOnSunday = $startOnSunday;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function getStartOfMonth(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);

        $date->modify('first day of this month');
        $date->setTime(0, 0, 0);

        return $date;
    }

    public function getStartOfLastMonth(): DateTimeInterface
    {
        $newDate = $this->createDateTime('first day of -1 month');
        $newDate->setTime(0, 0, 0);

        return $newDate;
    }

    private function getDate(DateTimeInterface|string|null $date = null): DateTime
    {
        if ($date === null) {
            $date = 'now';
        }

        if (\is_string($date)) {
            return $this->createDateTime($date);
        }

        return DateTime::createFromInterface($date);
    }

    public function getStartOfWeek(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);
        $firstDay = 1;

        if ($this->startOnSunday) {
            $firstDay = 7;

            // if today = sunday => increase week by one
            if ($date->format('N') !== '7') {
                $date->modify('-1 week');
            }
        }

        return $this->createWeekDateTime($date->format('o'), $date->format('W'), $firstDay, 0, 0, 0);
    }

    public function getEndOfWeek(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);
        $lastDay = 7;

        if ($this->startOnSunday) {
            $lastDay = 6;

            // only change when today is not sunday
            if ($date->format('N') === '7') {
                $date->modify('+1 week');
            }
        }

        return $this->createWeekDateTime($date->format('o'), $date->format('W'), $lastDay, 23, 59, 59);
    }

    public function getEndOfMonth(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);

        $date = $date->modify('last day of this month');
        $date->setTime(23, 59, 59);

        return $date;
    }

    public function getEndOfLastMonth(): DateTimeInterface
    {
        $newDate = $this->createDateTime('last day of -1 month');
        $newDate->setTime(23, 59, 59);

        return $newDate;
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
        return new DateTime($datetime, $this->getTimezone());
    }

    public function create(string $datetime = 'now'): \DateTimeImmutable
    {
        return new \DateTimeImmutable($datetime, $this->getTimezone());
    }

    /**
     * @param string $format
     * @param null|string $datetime
     * @return bool|DateTime
     */
    public function createDateTimeFromFormat(string $format, ?string $datetime = 'now'): bool|DateTime
    {
        return DateTime::createFromFormat($format, $datetime ?? 'now', $this->getTimezone());
    }

    public function createStartOfYear(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);

        $date->modify('first day of january 00:00:00');

        return $date;
    }

    public function createEndOfYear(DateTimeInterface|string|null $date = null): DateTime
    {
        $date = $this->getDate($date);

        $date->modify('last day of december 23:59:59');

        return $date;
    }

    public function createStartOfFinancialYear(?string $financialYear = null): DateTime
    {
        $defaultDate = $this->createDateTime('01 january this year 00:00:00');

        if (null === $financialYear) {
            return $defaultDate;
        }

        $financialYear = $this->createDateTime($financialYear);
        $financialYear->setDate((int) $defaultDate->format('Y'), (int) $financialYear->format('m'), (int) $financialYear->format('d'));

        $now = $this->createDateTime('00:00:00');

        if ($financialYear >= $now) {
            $financialYear->modify('-1 year');
        }

        return $financialYear;
    }

    public function createEndOfFinancialYear(DateTimeInterface $financialYear): DateTime
    {
        $yearEnd = DateTime::createFromInterface($financialYear);
        $yearEnd->modify('+1 year')->modify('-1 day')->setTime(23, 59, 59);

        return $yearEnd;
    }
}

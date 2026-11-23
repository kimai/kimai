<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Calculator;

use App\Entity\User;

final class WorkingTimeCalculatorDay implements WorkingTimeCalculator
{
    public const WORK_HOURS_MONDAY = 'work_monday';
    public const WORK_HOURS_TUESDAY = 'work_tuesday';
    public const WORK_HOURS_WEDNESDAY = 'work_wednesday';
    public const WORK_HOURS_THURSDAY = 'work_thursday';
    public const WORK_HOURS_FRIDAY = 'work_friday';
    public const WORK_HOURS_SATURDAY = 'work_saturday';
    public const WORK_HOURS_SUNDAY = 'work_sunday';

    public function __construct(private readonly User $user)
    {
    }

    public function getWorkHoursForDay(\DateTimeInterface $dateTime): int
    {
        return (int) match ($dateTime->format('N')) {
            '1' => $this->user->getPreferenceValue(self::WORK_HOURS_MONDAY, 0, false),
            '2' => $this->user->getPreferenceValue(self::WORK_HOURS_TUESDAY, 0, false),
            '3' => $this->user->getPreferenceValue(self::WORK_HOURS_WEDNESDAY, 0, false),
            '4' => $this->user->getPreferenceValue(self::WORK_HOURS_THURSDAY, 0, false),
            '5' => $this->user->getPreferenceValue(self::WORK_HOURS_FRIDAY, 0, false),
            '6' => $this->user->getPreferenceValue(self::WORK_HOURS_SATURDAY, 0, false),
            '7' => $this->user->getPreferenceValue(self::WORK_HOURS_SUNDAY, 0, false),
            default => throw new \Exception('Unknown day: ' . $dateTime->format('Y-m-d'))
        };
    }

    public function isWorkDay(\DateTimeInterface $dateTime): bool
    {
        return $this->getWorkHoursForDay($dateTime) > 0;
    }
}

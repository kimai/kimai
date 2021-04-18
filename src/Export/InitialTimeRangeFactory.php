<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\User;
use App\Form\Type\ExportTimeRangeType;
use App\Timesheet\DateTimeFactory;
use DateTime;
use DateTimeZone;

class InitialTimeRangeFactory
{
    protected function getRangeType(?User $user = null): string
    {
        $configuredType = $user->getPreferenceValue('export.initial_time_range');
        if (!\is_string($configuredType)
            || !\in_array($configuredType, ExportTimeRangeType::ALLOWED_TIME_RANGES)
        ) {
            // Fallback to monthly ranges, which were the standard implementation in the past.
            return ExportTimeRangeType::TIME_RANGE_CURRENT_MONTH;
        }

        return $configuredType;
    }

    /**
     * @param User|null $user
     * @return DateTimeFactory
     */
    protected function getDateTimeFactory(?User $user = null): DateTimeFactory
    {
        $timezone = ($user === null)
            ? date_default_timezone_get()
            : $user->getTimezone();

        return new DateTimeFactory(new DateTimeZone($timezone));
    }

    /**
     * @param User|null $user
     * @return DateTime
     */
    public function getStart(?User $user = null): DateTime
    {
        $dateTimeFactory = $this->getDateTimeFactory($user);

        $type = $this->getRangeType($user);
        if ($type === ExportTimeRangeType::TIME_RANGE_CURRENT_DECADE) {
            return $dateTimeFactory->getStartOfDecade();
        } elseif ($type === ExportTimeRangeType::TIME_RANGE_CURRENT_YEAR) {
            return $dateTimeFactory->getStartOfYear();
        } else {
            return $dateTimeFactory->getStartOfMonth();
        }
    }

    /**
     * @param User|null $user
     * @return DateTime
     */
    public function getEnd(?User $user = null): DateTime
    {
        $dateTimeFactory = $this->getDateTimeFactory($user);

        $type = $this->getRangeType($user);
        if ($type === ExportTimeRangeType::TIME_RANGE_CURRENT_DECADE) {
            return $dateTimeFactory->getEndOfDecade();
        } elseif ($type === ExportTimeRangeType::TIME_RANGE_CURRENT_YEAR) {
            return $dateTimeFactory->getEndOfYear();
        } else {
            return $dateTimeFactory->getEndOfMonth();
        }
    }

    /**
     * @param User|null $user
     * @return DateTime[]
     */
    public function getRange(?User $user = null): array
    {
        return [$this->getStart($user), $this->getEnd($user)];
    }
}

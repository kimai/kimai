<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use JMS\Serializer\Annotation as Serializer;

#[Serializer\ExclusionPolicy('none')]
final class TimesheetConfig
{
    /**
     * The time-tracking mode, see also: https://www.kimai.org/documentation/timesheet.html#tracking-modes
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $trackingMode = 'default';
    /**
     * Default begin datetime in PHP format
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private string $defaultBeginTime = 'now';
    /**
     * How many running timesheets a user is allowed to have at the same time
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'integer')]
    private int $activeEntriesHardLimit = 1;
    /**
     * Whether entries for future times are allowed
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    private bool $isAllowFutureTimes = true;
    /**
     * Whether overlapping entries are allowed
     *
     * @phpstan-ignore-next-line
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    private bool $isAllowOverlapping = true;

    public function setTrackingMode(string $trackingMode): void
    {
        $this->trackingMode = $trackingMode;
    }

    public function setDefaultBeginTime(string $defaultBeginTime): void
    {
        $this->defaultBeginTime = $defaultBeginTime;
    }

    public function setActiveEntriesHardLimit(int $activeEntriesHardLimit): void
    {
        $this->activeEntriesHardLimit = $activeEntriesHardLimit;
    }

    public function setIsAllowFutureTimes(bool $isAllowFutureTimes): void
    {
        $this->isAllowFutureTimes = $isAllowFutureTimes;
    }

    public function setIsAllowOverlapping(bool $isAllowOverlapping): void
    {
        $this->isAllowOverlapping = $isAllowOverlapping;
    }
}

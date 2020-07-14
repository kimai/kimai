<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("none")
 * @Serializer\AccessorOrder("custom", custom = {"trackingMode", "defaultBeginTime", "activeEntriesHardLimit", "activeEntriesSoftLimit", "isAllowFutureTimes", "isAllowOverlapping"})
 */
final class TimesheetConfig
{
    /**
     * The time-tracking mode, see also: https://www.kimai.org/documentation/timesheet.html#tracking-modes
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    private $trackingMode = 'default';
    /**
     * Default begin datetime in PHP format
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    private $defaultBeginTime = 'now';
    /**
     * How many running timesheets a user is allowed to have at the same time
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="integer")
     */
    private $activeEntriesHardLimit = 1;
    /**
     * How many running timesheets a user is allowed before a warning is shown
     *
     * @var int
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="integer")
     */
    private $activeEntriesSoftLimit = 1;
    /**
     * Whether entries for future times are allowed
     *
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="boolean")
     */
    private $isAllowFutureTimes = true;
    /**
     * Whether overlapping entries are allowed
     *
     * @var bool
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="boolean")
     */
    private $isAllowOverlapping = true;

    public function setTrackingMode(string $trackingMode): TimesheetConfig
    {
        $this->trackingMode = $trackingMode;

        return $this;
    }

    public function setDefaultBeginTime(string $defaultBeginTime): TimesheetConfig
    {
        $this->defaultBeginTime = $defaultBeginTime;

        return $this;
    }

    public function setActiveEntriesHardLimit(int $activeEntriesHardLimit): TimesheetConfig
    {
        $this->activeEntriesHardLimit = $activeEntriesHardLimit;

        return $this;
    }

    public function setActiveEntriesSoftLimit(int $activeEntriesSoftLimit): TimesheetConfig
    {
        $this->activeEntriesSoftLimit = $activeEntriesSoftLimit;

        return $this;
    }

    public function setIsAllowFutureTimes(bool $isAllowFutureTimes): TimesheetConfig
    {
        $this->isAllowFutureTimes = $isAllowFutureTimes;

        return $this;
    }

    public function setIsAllowOverlapping(bool $isAllowOverlapping): TimesheetConfig
    {
        $this->isAllowOverlapping = $isAllowOverlapping;

        return $this;
    }
}

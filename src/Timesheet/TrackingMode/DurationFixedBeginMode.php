<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Timesheet\UserDateTimeFactory;
use Symfony\Component\HttpFoundation\Request;

final class DurationFixedBeginMode implements TrackingModeInterface
{
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;

    public function __construct(UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->dateTime = $dateTime;
        $this->configuration = $configuration;
    }

    public function canEditBegin(): bool
    {
        return false;
    }

    public function canEditEnd(): bool
    {
        return false;
    }

    public function canEditDuration(): bool
    {
        return true;
    }

    public function canUpdateTimesWithAPI(): bool
    {
        return false;
    }

    public function create(Timesheet $timesheet, ?Request $request = null): void
    {
        if (null === $timesheet->getBegin()) {
            $timesheet->setBegin($this->dateTime->createDateTime());
        }

        $timesheet->getBegin()->modify($this->configuration->getDefaultBeginTime());
    }

    public function getId(): string
    {
        return 'duration_fixed_begin';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return false;
    }
}

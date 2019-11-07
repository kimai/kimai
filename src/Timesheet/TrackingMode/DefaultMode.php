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
use App\Timesheet\RoundingService;
use App\Timesheet\UserDateTimeFactory;
use Symfony\Component\HttpFoundation\Request;

final class DefaultMode extends AbstractTrackingMode
{
    /**
     * @var RoundingService
     */
    private $rounding;

    public function __construct(UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration, RoundingService $rounding)
    {
        parent::__construct($dateTime, $configuration);
        $this->rounding = $rounding;
    }

    public function canEditBegin(): bool
    {
        return true;
    }

    public function canEditEnd(): bool
    {
        return true;
    }

    public function canEditDuration(): bool
    {
        return false;
    }

    public function canUpdateTimesWithAPI(): bool
    {
        return true;
    }

    public function getId(): string
    {
        return 'default';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return true;
    }

    public function create(Timesheet $timesheet, Request $request): void
    {
        parent::create($timesheet, $request);

        if (null === $timesheet->getBegin()) {
            $timesheet->setBegin($this->dateTime->createDateTime());
        }

        $this->rounding->roundBegin($timesheet);

        if (null !== $timesheet->getEnd()) {
            $this->rounding->roundEnd($timesheet);

            if (null !== $timesheet->getDuration()) {
                $this->rounding->roundDuration($timesheet);
            }
        }
    }
}

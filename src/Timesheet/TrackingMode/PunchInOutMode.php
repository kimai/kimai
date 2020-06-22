<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use App\Timesheet\UserDateTimeFactory;
use Symfony\Component\HttpFoundation\Request;

final class PunchInOutMode implements TrackingModeInterface
{
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;

    public function __construct(UserDateTimeFactory $dateTime)
    {
        $this->dateTime = $dateTime;
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
        return false;
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
    }

    public function getId(): string
    {
        return 'punch';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return true;
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

final class DurationFixedBeginMode implements TrackingModeInterface
{
    use TrackingModeTrait;

    public function __construct(private SystemConfiguration $configuration)
    {
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
            $timesheet->setBegin(new DateTime('now', $this->getTimezone($timesheet)));
        }

        $newBegin = clone $timesheet->getBegin();

        // this prevents the problem that "now" is being ignored in modify()
        $beginTime = (new DateTime($this->configuration->getTimesheetDefaultBeginTime(), $newBegin->getTimezone()))->format('H:i:s');
        $newBegin->modify($beginTime);

        $timesheet->setBegin($newBegin);
    }

    public function getId(): string
    {
        return 'duration_fixed_begin';
    }

    public function canSeeBeginAndEndTimes(): bool
    {
        return false;
    }

    public function getEditTemplate(): string
    {
        return 'timesheet/edit-default.html.twig';
    }
}

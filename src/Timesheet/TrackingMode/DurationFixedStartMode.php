<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\TrackingMode;

use App\Entity\Timesheet;
use Symfony\Component\HttpFoundation\Request;

class DurationFixedStartMode extends AbstractTrackingMode
{
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

    public function create(Timesheet $timesheet, Request $request): void
    {
        $timesheet->getBegin()->modify($this->configuration->getDefaultBeginTime());
    }
}

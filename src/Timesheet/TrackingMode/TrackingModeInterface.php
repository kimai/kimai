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

/**
 * A tracking-mode defines the behaviour of the edit timesheet record forms for user.
 */
interface TrackingModeInterface
{
    /**
     * Set default values on this new timesheet entity.
     *
     * @param Timesheet $timesheet
     * @param Request $request
     */
    public function create(Timesheet $timesheet, Request $request): void;

    public function canEditBegin(): bool;

    public function canEditEnd(): bool;

    public function canEditDuration(): bool;

    public function canUpdateTimesWithAPI(): bool;
}

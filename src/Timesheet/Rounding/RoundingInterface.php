<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet\Rounding;

use App\Entity\Timesheet;

/**
 * Apply rounding rules to the given timesheet.
 */
interface RoundingInterface
{
    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    public function roundBegin(Timesheet $record, $minutes);

    /**
     * @param Timesheet $record
     * @param int $minutes
     */
    public function roundEnd(Timesheet $record, $minutes);

    /**
     * @param Timesheet $record
     * @param $minutes
     */
    public function roundDuration(Timesheet $record, $minutes);
}

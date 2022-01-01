<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\Timesheet;

/**
 * Implementation to calculate the rate for a timesheet record.
 */
interface RateServiceInterface
{
    public function calculate(Timesheet $record): Rate;
}

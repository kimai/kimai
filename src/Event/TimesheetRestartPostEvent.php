<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Timesheet;

final class TimesheetRestartPostEvent extends AbstractTimesheetEvent
{
    /**
     * @var Timesheet
     */
    private $original;

    public function __construct(Timesheet $new, Timesheet $original)
    {
        parent::__construct($new);
        $this->original = $original;
    }

    public function getOriginalTimesheet(): Timesheet
    {
        return $this->original;
    }
}

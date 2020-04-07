<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\Timesheet;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used to call functions after timesheet creations/updates
 */
abstract class AbstractTimesheetEvent extends Event
{
    /**
     * @var Timesheet
     */
    private $timesheet;

    public function __construct(Timesheet $timesheet)
    {
        $this->timesheet = $timesheet;
    }

    public function getTimesheet(): Timesheet
    {
        return $this->timesheet;
    }
}

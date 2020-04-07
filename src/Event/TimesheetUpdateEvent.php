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
final class TimesheetUpdateEvent extends Event
{
    // TODO used in multi update context, needs to be replaced
    public const TIMESHEET_UPDATE = 'timesheet.update';
    public const TIMESHEET_DELETE = 'timesheet.delete';

    // TODO removed without replacement, all covered by update events
    // public const TIMESHEET_RESTART = 'timesheet.restart';
    // public const TIMESHEET_DUPLICATE = 'timesheet.duplicate';

    /**
     * @var Timesheet[]
     */
    private $entities;

    /**
     * TimesheetUpdateEvent constructor.
     * @param Timesheet|array $entities
     */
    public function __construct($entities = [])
    {
        if ($entities instanceof Timesheet) {
            $this->entities[] = $entities;

            return;
        }

        $this->entities = $entities;
    }

    /**
     * @return Timesheet|Timesheet[]
     */
    public function getEntity()
    {
        if (count($this->entities) === 1) {
            return reset($this->entities);
        }

        return $this->entities;
    }
}

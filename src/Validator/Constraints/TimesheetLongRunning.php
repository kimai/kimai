<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetLongRunning extends TimesheetConstraint
{
    public const LONG_RUNNING = 'kimai-timesheet-long-running-01';
    public const MAXIMUM = 'kimai-timesheet-long-running-02';

    protected static $errorNames = [
        self::LONG_RUNNING => 'TIMESHEET_LONG_RUNNING',
        self::MAXIMUM => 'MAXIMUM',
    ];

    public $message = 'Maximum duration of {{ value }} hours exceeded.';
    public $maximumMessage = 'Maximum duration exceeded.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

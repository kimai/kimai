<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetRestart extends TimesheetConstraint
{
    public const START_DISALLOWED = 'kimai-timesheet-restart-01';

    protected static $errorNames = [
        self::START_DISALLOWED => 'You are not allowed to start this timesheet record.',
    ];

    public $message = 'You are not allowed to start this timesheet record.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

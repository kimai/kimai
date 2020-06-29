<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetLockdown extends TimesheetConstraint
{
    public const PERIOD_LOCKED = 'kimai-timesheet-lockdown-01';

    protected static $errorNames = [
        self::PERIOD_LOCKED => 'Please change begin/end, as this timesheet is in a locked period.',
    ];

    public $message = 'This period is locked.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

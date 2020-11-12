<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetOverlapping extends TimesheetConstraint
{
    public const RECORD_OVERLAPPING = 'kimai-timesheet-overlapping-01';

    protected static $errorNames = [
        self::RECORD_OVERLAPPING => 'You already have an entry for this time.',
    ];

    public $message = 'You already have an entry for this time.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

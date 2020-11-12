<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetFutureTimes extends TimesheetConstraint
{
    public const BEGIN_IN_FUTURE_ERROR = 'kimai-timesheet-future-times-01';

    protected static $errorNames = [
        self::BEGIN_IN_FUTURE_ERROR => 'The begin date cannot be in the future.',
    ];

    public $message = 'The begin date cannot be in the future.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetZeroLength extends TimesheetConstraint
{
    public const ZERO_LENGTH_ERROR = 'kimai-timesheet-zero-length-01';

    protected static $errorNames = [
        self::ZERO_LENGTH_ERROR => 'The Duration can not be zero.',
    ];

    public $message = 'The Duration can not be zero.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

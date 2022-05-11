<?php

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


<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

final class TimesheetNegativeDuration extends TimesheetConstraint
{
    public const NEGATIVE_DURATION_ERROR = 'kimai-timesheet-negative-duration-01';

    protected const ERROR_NAMES = [
        self::NEGATIVE_DURATION_ERROR => 'Duration cannot be negative.',
    ];

    public string $message = 'Duration cannot be negative.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

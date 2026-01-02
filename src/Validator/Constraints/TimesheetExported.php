<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Validator\Attribute\TimesheetConstraint;
use Symfony\Component\Validator\Constraint;

#[TimesheetConstraint]
final class TimesheetExported extends Constraint
{
    public const string TIMESHEET_EXPORTED = 'kimai-timesheet-exported-01';

    protected const array ERROR_NAMES = [
        self::TIMESHEET_EXPORTED => 'This timesheet is already exported.',
    ];

    public string $message = 'This timesheet is already exported.';

    /**
     * @var \DateTime|string|null
     */
    public null|\DateTime|string $now;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

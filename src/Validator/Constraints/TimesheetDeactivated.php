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
final class TimesheetDeactivated extends Constraint
{
    public const string DISABLED_ACTIVITY_ERROR = 'kimai-timesheet-deactivated-activity';
    public const string DISABLED_PROJECT_ERROR = 'kimai-timesheet-deactivated-project';
    public const string DISABLED_CUSTOMER_ERROR = 'kimai-timesheet-deactivated-customer';

    protected const array ERROR_NAMES = [
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
    ];

    public string $message = 'This timesheet has invalid settings.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

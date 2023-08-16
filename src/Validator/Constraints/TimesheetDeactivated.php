<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class TimesheetDeactivated extends TimesheetConstraint
{
    public const DISABLED_ACTIVITY_ERROR = 'kimai-timesheet-deactivated-activity';
    public const DISABLED_PROJECT_ERROR = 'kimai-timesheet-deactivated-project';
    public const DISABLED_CUSTOMER_ERROR = 'kimai-timesheet-deactivated-customer';

    protected const ERROR_NAMES = [
        self::DISABLED_ACTIVITY_ERROR => 'Cannot start a disabled activity.',
        self::DISABLED_PROJECT_ERROR => 'Cannot start a disabled project.',
        self::DISABLED_CUSTOMER_ERROR => 'Cannot start a disabled customer.',
    ];

    public string $message = 'This timesheet has invalid settings.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

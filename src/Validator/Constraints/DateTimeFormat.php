<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class DateTimeFormat extends Constraint
{
    public const INVALID_FORMAT = 'kimai-datetime-00';

    protected const ERROR_NAMES = [
        self::INVALID_FORMAT => 'This value is not a valid datetime.',
    ];

    public ?string $separator = null;
    public ?string $message = 'This value is not a valid datetime.';

    // Before: The given value is not a valid datetime format.
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}

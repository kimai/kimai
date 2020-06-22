<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DateTimeFormat extends Constraint
{
    public const INVALID_FORMAT = 'kimai-datetime-00';

    protected static $errorNames = [
        self::INVALID_FORMAT => 'The given value is not a valid datetime format.',
    ];

    public $message = 'This datetime format is invalid.';

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}

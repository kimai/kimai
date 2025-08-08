<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class IcalLink extends Constraint
{
    public const INVALID_URL = 'ical_link.invalid_url';
    public const INVALID_ICS = 'ical_link.invalid_ics';

    public string $message = 'This value is not a valid ICAL link.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
} 
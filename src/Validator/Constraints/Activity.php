<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Activity extends Constraint
{
    public const ACTIVITY_NUMBER_EXISTING = 'kimai-activity-00';

    protected const ERROR_NAMES = [
        self::ACTIVITY_NUMBER_EXISTING => 'The number %number% is already used.',
    ];

    public string $message = 'This activity has invalid settings.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

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
final class Team extends Constraint
{
    public const MISSING_TEAMLEAD = 'kimai-team-001';

    protected const ERROR_NAMES = [
        self::MISSING_TEAMLEAD => 'At least one team leader must be assigned to the team.',
    ];

    public string $message = 'The team has invalid settings.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

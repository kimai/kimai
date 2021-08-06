<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 */
class Team extends Constraint
{
    public const MISSING_TEAMLEAD = 'kimai-team-001';

    protected static $errorNames = [
        self::MISSING_TEAMLEAD => 'At least one team leader must be assigned to the team.',
    ];

    public $message = 'The team has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

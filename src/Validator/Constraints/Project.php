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
class Project extends Constraint
{
    public const END_BEFORE_BEGIN_ERROR = 'kimai-project-00';

    protected static $errorNames = [
        self::END_BEFORE_BEGIN_ERROR => 'End date must not be earlier then start date.',
    ];

    public $message = 'This project has invalid settings.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

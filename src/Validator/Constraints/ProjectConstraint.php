<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Validator\Constraint;

/**
 * Extend this class if you want to add dynamic project validation (eg. via a bundle).
 */
#[AutoconfigureTag]
abstract class ProjectConstraint extends Constraint
{
}

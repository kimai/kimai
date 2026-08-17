<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Attribute\TimesheetConstraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

abstract class AbstractConstraintTestCase extends TestCase
{
    protected function assertTimesheetConstraint(Constraint $constraint): void
    {
        $r = new \ReflectionClass($constraint);
        $attributes = $r->getAttributes(TimesheetConstraint::class);
        self::assertCount(1, $attributes);
        self::assertEquals(TimesheetConstraint::class, $attributes[0]->getName());
    }
}

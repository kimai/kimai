<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use App\Validator\Constraints\Timesheet as TimesheetEntityConstraint;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetValidator extends ConstraintValidator
{
    /**
     * @param TimesheetConstraint[] $constraints
     */
    public function __construct(
        #[TaggedIterator(TimesheetConstraint::class)]
        private iterable $constraints
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetEntityConstraint)) {
            throw new UnexpectedTypeException($constraint, TimesheetEntityConstraint::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        $groups = [Constraint::DEFAULT_GROUP];
        if ($this->context->getGroup() !== null) {
            $groups = [$this->context->getGroup()];
        }

        foreach ($this->constraints as $innerConstraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, $innerConstraint, $groups);
        }
    }
}

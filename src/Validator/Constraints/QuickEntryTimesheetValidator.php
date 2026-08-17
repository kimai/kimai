<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use App\Validator\Constraints\QuickEntryTimesheet as QuickEntryTimesheetConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class QuickEntryTimesheetValidator extends ConstraintValidator
{
    /**
     * @param class-string[] $constraints
     */
    public function __construct(
        private readonly array $constraints = []
    )
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuickEntryTimesheetConstraint) {
            throw new UnexpectedTypeException($constraint, QuickEntryTimesheetConstraint::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($value->getId() === null && $value->getDuration(false) === null) {
            return;
        }

        foreach ($this->constraints as $constraintClass) {
            $r = new \ReflectionClass($constraintClass);
            $innerConstraint = $r->newInstance();
            if (!$innerConstraint instanceof Constraint) {
                throw new \InvalidArgumentException('Attribute #[TimesheetConstraint] was applied to a non-constraint class: ' . $constraintClass);
            }

            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('duration')
                ->validate($value, $innerConstraint, [Constraint::DEFAULT_GROUP]);
        }
    }
}

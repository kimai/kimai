<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet;
use App\Validator\Constraints\QuickEntryTimesheet as QuickEntryTimesheetConstraint;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class QuickEntryTimesheetValidator extends ConstraintValidator
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
        if (!$constraint instanceof QuickEntryTimesheetConstraint) {
            throw new UnexpectedTypeException($constraint, QuickEntryTimesheetConstraint::class);
        }

        if (!\is_object($value) || !($value instanceof Timesheet)) {
            throw new UnexpectedTypeException($value, Timesheet::class);
        }

        $timesheet = $value;

        if ($timesheet->getId() === null && $timesheet->getDuration(false) === null) {
            return;
        }

        foreach ($this->constraints as $innerConstraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('duration')
                ->validate($timesheet, $innerConstraint, [Constraint::DEFAULT_GROUP]);
        }
    }
}

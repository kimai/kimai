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

class QuickEntryTimesheetValidator extends ConstraintValidator
{
    /**
     * @param Constraint[] $constraints
     */
    public function __construct(private iterable $constraints)
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

        /** @var TimesheetEntity $timesheet */
        $timesheet = $value;

        if ($timesheet->getId() === null && $timesheet->getDuration(false) === null) {
            return;
        }

        foreach ($this->constraints as $constraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->atPath('duration')
                ->validate($timesheet, $constraint, [Constraint::DEFAULT_GROUP]);
        }
    }
}

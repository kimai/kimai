<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetValidator extends ConstraintValidator
{
    /**
     * @var Constraint[]
     */
    private $constraints;

    /**
     * @param Constraint[] $constraints
     */
    public function __construct(iterable $constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetConstraint)) {
            throw new UnexpectedTypeException($constraint, Timesheet::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        foreach ($this->constraints as $constraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($timesheet, $constraint, [Constraint::DEFAULT_GROUP]);
        }
    }
}

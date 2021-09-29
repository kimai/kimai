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
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class QuickEntryTimesheetValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof QuickEntryTimesheetConstraint) {
            throw new UnexpectedTypeException($constraint, QuickEntryTimesheetConstraint::class);
        }

        if ($value === null || (\is_string($value) && empty(trim($value)))) {
            return;
        }

        /** @var TimesheetEntity $timesheet */
        $timesheet = $value;

        if ($timesheet->getId() === null && $timesheet->getDuration(false) === null) {
            return;
        }
        /*
                if ($timesheet->isExported()) {
                    $this->context->buildViolation('Cannot create record for disabled project.')
                        ->atPath('duration')
                        ->setTranslationDomain('validators')
                        ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                        ->addViolation();
                }
        */
        if (($project = $timesheet->getProject()) !== null) {
            if ($timesheet->getId() === null && !$project->isVisible()) {
                $this->context->buildViolation('Cannot create record for disabled project.')
                    ->atPath('duration')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                    ->addViolation();
            }
            if ($timesheet->getId() === null && !$project->getCustomer()->isVisible()) {
                $this->context->buildViolation('Cannot create record for disabled customer.')
                    ->atPath('duration')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                    ->addViolation();
            }
            if ($timesheet->getId() === null) {
                TimesheetValidator::validateProjectBeginAndEnd($timesheet, $this->context, 'duration');
            }
        }

        if (($activity = $timesheet->getActivity()) !== null) {
            if ($timesheet->getId() === null && !$activity->isVisible()) {
                $this->context->buildViolation('Cannot create record for disabled activity.')
                    ->atPath('duration')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::DISABLED_ACTIVITY_ERROR)
                    ->addViolation();
            }
        }

        if (null !== $activity && null !== $project && null !== $activity->getProject() && $activity->getProject() !== $project) {
            $this->context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                ->atPath('duration')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
                ->addViolation();
        }
    }
}

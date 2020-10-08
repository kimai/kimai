<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Form\MultiUpdate\TimesheetMultiUpdateDTO;
use App\Validator\Constraints\TimesheetMultiUpdate as TimesheetMultiUpdateConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetMultiUpdateValidator extends ConstraintValidator
{
    /**
     * @param TimesheetMultiUpdateDTO|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetMultiUpdateConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\TimesheetMultiUpdate');
        }

        if (!\is_object($value) || !($value instanceof TimesheetMultiUpdateDTO)) {
            return;
        }

        $this->validateActivityAndProject($value, $this->context);

        if (null !== $value->getFixedRate() && null !== $value->getHourlyRate()) {
            $this->context->buildViolation('Cannot set hourly rate and fixed rate at the same time.')
                ->atPath('fixedRate')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
                ->addViolation();

            $this->context->buildViolation('Cannot set hourly rate and fixed rate at the same time.')
                ->atPath('hourlyRate')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetMultiUpdateConstraint::HOURLY_RATE_FIXED_RATE)
                ->addViolation();
        }
    }

    /**
     * @param TimesheetMultiUpdateDTO $dto
     * @param ExecutionContextInterface $context
     */
    protected function validateActivityAndProject(TimesheetMultiUpdateDTO $dto, ExecutionContextInterface $context)
    {
        $activity = $dto->getActivity();
        $project = $dto->getProject();

        // non global activity without project
        if (null !== $activity && null !== $activity->getProject() && null === $project) {
            $context->buildViolation('Missing project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetMultiUpdateConstraint::MISSING_PROJECT_ERROR)
                ->addViolation();

            return;
        }

        // only project was chosen
        if (null === $activity && null !== $project) {
            $context->buildViolation('You need to choose an activity, if the project should be changed.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetMultiUpdateConstraint::MISSING_ACTIVITY_ERROR)
                ->addViolation();

            return;
        }

        if (null !== $activity) {
            if (null !== $activity->getProject() && $activity->getProject() !== $project) {
                $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetMultiUpdateConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
                    ->addViolation();

                return;
            }

            if (!$activity->isVisible()) {
                $context->buildViolation('Cannot assign a disabled activity.')
                    ->atPath('activity')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetMultiUpdateConstraint::DISABLED_ACTIVITY_ERROR)
                    ->addViolation();
            }
        }

        if (null !== $project) {
            if (!$project->isVisible()) {
                $context->buildViolation('Cannot assign a disabled project.')
                    ->atPath('project')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetMultiUpdateConstraint::DISABLED_PROJECT_ERROR)
                    ->addViolation();
            }

            if (!$project->getCustomer()->isVisible()) {
                $context->buildViolation('Cannot assign a disabled customer.')
                    ->atPath('customer')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetMultiUpdateConstraint::DISABLED_CUSTOMER_ERROR)
                    ->addViolation();
            }
        }
    }
}

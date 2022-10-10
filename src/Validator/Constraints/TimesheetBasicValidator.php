<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetBasicValidator extends ConstraintValidator
{
    /**
     * @param TimesheetEntity $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetBasic)) {
            throw new UnexpectedTypeException($constraint, TimesheetBasic::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($timesheet, TimesheetEntity::class);
        }

        $this->validateBeginAndEnd($timesheet, $this->context);
        $this->validateActivityAndProject($timesheet, $this->context);
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateBeginAndEnd(TimesheetEntity $timesheet, ExecutionContextInterface $context)
    {
        $begin = $timesheet->getBegin();
        $end = $timesheet->getEnd();

        if (null === $begin) {
            $context->buildViolation('You must submit a begin date.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_BEGIN_ERROR)
                ->addViolation();

            return;
        }

        if (null !== $end && $begin > $end) {
            $context->buildViolation('End date must not be earlier then start date.')
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateActivityAndProject(TimesheetEntity $timesheet, ExecutionContextInterface $context)
    {
        if (null === ($activity = $timesheet->getActivity())) {
            $context->buildViolation('An activity needs to be selected.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === ($project = $timesheet->getProject())) {
            $context->buildViolation('A project needs to be selected.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_PROJECT_ERROR)
                ->addViolation();
        }

        if (null === $activity || null === $project) {
            return;
        }

        if (null !== $activity->getProject() && $activity->getProject() !== $project) {
            $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR)
                ->addViolation();
        }

        $timesheetEnd = $timesheet->getEnd();
        $newOrStarted = null === $timesheetEnd || $timesheet->getId() === null;

        if ($newOrStarted && !$activity->isVisible()) {
            $context->buildViolation('Cannot start a disabled activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::DISABLED_ACTIVITY_ERROR)
                ->addViolation();
        }

        if ($newOrStarted && !$project->isVisible()) {
            $context->buildViolation('Cannot start a disabled project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::DISABLED_PROJECT_ERROR)
                ->addViolation();
        }

        if ($newOrStarted && !$project->getCustomer()->isVisible()) {
            $context->buildViolation('Cannot start a disabled customer.')
                ->atPath('customer')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::DISABLED_CUSTOMER_ERROR)
                ->addViolation();
        }

        if (!$project->isGlobalActivities() && $activity->isGlobal()) {
            $context->buildViolation('Global activities are forbidden for the selected project.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::PROJECT_DISALLOWS_GLOBAL_ACTIVITY)
                ->addViolation();
        }

        $projectBegin = $project->getStart();
        $projectEnd = $project->getEnd();

        if (null === $projectBegin && null === $projectEnd) {
            return;
        }

        $pathStart = 'begin';
        $pathEnd = 'end';

        $timesheetStart = $timesheet->getBegin();
        $timesheetEnd = $timesheet->getEnd();

        if (null !== $timesheetStart) {
            if (null !== $projectBegin && $timesheetStart->getTimestamp() < $projectBegin->getTimestamp()) {
                $context->buildViolation('The project has not started at that time.')
                    ->atPath($pathStart)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_NOT_STARTED)
                    ->addViolation();
            } elseif (null !== $projectEnd && $timesheetStart->getTimestamp() > $projectEnd->getTimestamp()) {
                $context->buildViolation('The project is finished at that time.')
                    ->atPath($pathStart)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_ALREADY_ENDED)
                    ->addViolation();
            }
        }

        if (null !== $timesheetEnd) {
            if (null !== $projectEnd && $timesheetEnd->getTimestamp() > $projectEnd->getTimestamp()) {
                $context->buildViolation('The project is finished at that time.')
                    ->atPath($pathEnd)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_ALREADY_ENDED)
                    ->addViolation();
            } elseif (null !== $projectBegin && $timesheetEnd->getTimestamp() < $projectBegin->getTimestamp()) {
                $context->buildViolation('The project has not started at that time.')
                    ->atPath($pathEnd)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_NOT_STARTED)
                    ->addViolation();
            }
        }
    }
}

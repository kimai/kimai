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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetValidator extends ConstraintValidator
{
    /**
     * @var TimesheetConstraint[]
     */
    private $constraints;

    /**
     * @param TimesheetConstraint[] $constraints
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

        $this->validateBeginAndEnd($timesheet, $this->context);
        $this->validateActivityAndProject($timesheet, $this->context);
        $this->validateActiveLimit($timesheet, $this->context);

        foreach ($this->constraints as $constraint) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($timesheet, $constraint, [Constraint::DEFAULT_GROUP]);
        }
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateActiveLimit(TimesheetEntity $timesheet, ExecutionContextInterface $context)
    {
        // TODO check active entries against hard_limit
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateBeginAndEnd(TimesheetEntity $timesheet, ExecutionContextInterface $context)
    {
        if (null === $timesheet->getBegin()) {
            $context->buildViolation('You must submit a begin date.')
                ->atPath('begin')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_BEGIN_ERROR)
                ->addViolation();

            return;
        }

        if (null !== $timesheet->getBegin() && null !== $timesheet->getEnd() && $timesheet->getEnd()->getTimestamp() < $timesheet->getBegin()->getTimestamp()) {
            $context->buildViolation('End date must not be earlier then start date.')
                ->atPath('end')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::END_BEFORE_BEGIN_ERROR)
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
            $context->buildViolation('A timesheet must have an activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === ($project = $timesheet->getProject())) {
            $context->buildViolation('A timesheet must have a project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::MISSING_PROJECT_ERROR)
                ->addViolation();
        }

        if (null === $activity || null === $project) {
            return;
        }

        if (null !== $activity->getProject() && $activity->getProject() !== $project) {
            $context->buildViolation('Project mismatch, project specific activity and timesheet project are different.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::ACTIVITY_PROJECT_MISMATCH_ERROR)
                ->addViolation();
        }

        $timesheetEnd = $timesheet->getEnd();

        if (null === $timesheetEnd && !$activity->isVisible()) {
            $context->buildViolation('Cannot start a disabled activity.')
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === $timesheetEnd && !$project->isVisible()) {
            $context->buildViolation('Cannot start a disabled project.')
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_PROJECT_ERROR)
                ->addViolation();
        }

        if (null === $timesheetEnd && !$project->getCustomer()->isVisible()) {
            $context->buildViolation('Cannot start a disabled customer.')
                ->atPath('customer')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetConstraint::DISABLED_CUSTOMER_ERROR)
                ->addViolation();
        }

        $projectBegin = $project->getStart();
        $projectEnd = $project->getEnd();

        if (null !== $projectBegin || null !== $projectEnd) {
            $timesheetStart = $timesheet->getBegin();
            $timesheetEnd = $timesheet->getEnd();

            if (null !== $timesheetStart) {
                if (null !== $projectBegin && $timesheetStart->getTimestamp() < $projectBegin->getTimestamp()) {
                    $context->buildViolation('The project has not started at that time.')
                        ->atPath('begin')
                        ->setTranslationDomain('validators')
                        ->setCode(TimesheetConstraint::PROJECT_NOT_STARTED)
                        ->addViolation();
                } elseif (null !== $projectEnd && $timesheetStart->getTimestamp() > $projectEnd->getTimestamp()) {
                    $context->buildViolation('The project is finished at that time.')
                        ->atPath('begin')
                        ->setTranslationDomain('validators')
                        ->setCode(TimesheetConstraint::PROJECT_ALREADY_ENDED)
                        ->addViolation();
                }
            }

            if (null !== $timesheetEnd) {
                if (null !== $projectEnd && $timesheetEnd->getTimestamp() > $projectEnd->getTimestamp()) {
                    $context->buildViolation('The project is finished at that time.')
                        ->atPath('end')
                        ->setTranslationDomain('validators')
                        ->setCode(TimesheetConstraint::PROJECT_ALREADY_ENDED)
                        ->addViolation();
                } elseif (null !== $projectBegin && $timesheetEnd->getTimestamp() < $projectBegin->getTimestamp()) {
                    $context->buildViolation('The project has not started at that time.')
                        ->atPath('end')
                        ->setTranslationDomain('validators')
                        ->setCode(TimesheetConstraint::PROJECT_NOT_STARTED)
                        ->addViolation();
                }
            }
        }
    }
}

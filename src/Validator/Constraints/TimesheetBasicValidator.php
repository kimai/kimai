<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetBasicValidator extends ConstraintValidator
{
    public function __construct(private SystemConfiguration $systemConfiguration)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetBasic)) {
            throw new UnexpectedTypeException($constraint, TimesheetBasic::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        $this->validateBeginAndEnd($value, $this->context);
        $this->validateActivityAndProject($value, $this->context);
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateBeginAndEnd(TimesheetEntity $timesheet, ExecutionContextInterface $context): void
    {
        $begin = $timesheet->getBegin();
        $end = $timesheet->getEnd();

        if (null === $begin) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::MISSING_BEGIN_ERROR))
                ->atPath('begin_date')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_BEGIN_ERROR)
                ->addViolation();

            return;
        }

        if (null !== $end && $begin > $end) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::END_BEFORE_BEGIN_ERROR))
                ->atPath('end_date')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::END_BEFORE_BEGIN_ERROR)
                ->addViolation();
        }
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validateActivityAndProject(TimesheetEntity $timesheet, ExecutionContextInterface $context): void
    {
        $activity = $timesheet->getActivity();

        if ($this->systemConfiguration->isTimesheetRequiresActivity() && null === $activity) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::MISSING_ACTIVITY_ERROR))
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_ACTIVITY_ERROR)
                ->addViolation();
        }

        if (null === ($project = $timesheet->getProject())) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::MISSING_PROJECT_ERROR))
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::MISSING_PROJECT_ERROR)
                ->addViolation();
        }

        $hasActivity = null !== $activity;

        if (null === $project) {
            return;
        }

        if ($hasActivity && null !== $activity->getProject() && $activity->getProject() !== $project) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR))
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::ACTIVITY_PROJECT_MISMATCH_ERROR)
                ->addViolation();
        }

        if ($hasActivity && !$project->isGlobalActivities() && $activity->isGlobal()) {
            $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::PROJECT_DISALLOWS_GLOBAL_ACTIVITY))
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetBasic::PROJECT_DISALLOWS_GLOBAL_ACTIVITY)
                ->addViolation();
        }

        $pathStart = 'begin_date';
        $pathEnd = 'end_date';

        $projectBegin = $project->getStart();
        $projectEnd = $project->getEnd();

        if (null === $projectBegin && null === $projectEnd) {
            return;
        }

        $timesheetStart = $timesheet->getBegin();
        $timesheetEnd = $timesheet->getEnd();

        if (null !== $timesheetStart) {
            if (null !== $projectBegin && $timesheetStart->getTimestamp() < $projectBegin->getTimestamp()) {
                $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::PROJECT_NOT_STARTED))
                    ->atPath($pathStart)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_NOT_STARTED)
                    ->addViolation();
            } elseif (null !== $projectEnd && $timesheetStart->getTimestamp() > $projectEnd->getTimestamp()) {
                $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::PROJECT_ALREADY_ENDED))
                    ->atPath($pathStart)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_ALREADY_ENDED)
                    ->addViolation();
            }
        }

        if (null !== $timesheetEnd) {
            if (null !== $projectEnd && $timesheetEnd->getTimestamp() > $projectEnd->getTimestamp()) {
                $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::PROJECT_ALREADY_ENDED))
                    ->atPath($pathEnd)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_ALREADY_ENDED)
                    ->addViolation();
            } elseif (null !== $projectBegin && $timesheetEnd->getTimestamp() < $projectBegin->getTimestamp()) {
                $context->buildViolation(TimesheetBasic::getErrorName(TimesheetBasic::PROJECT_NOT_STARTED))
                    ->atPath($pathEnd)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetBasic::PROJECT_NOT_STARTED)
                    ->addViolation();
            }
        }
    }
}

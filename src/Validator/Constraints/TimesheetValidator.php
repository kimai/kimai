<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet as TimesheetEntity;
use App\Timesheet\TrackingModeService;
use App\Validator\Constraints\Timesheet as TimesheetConstraint;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TimesheetValidator extends ConstraintValidator
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $auth;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;
    /**
     * @var TrackingModeService
     */
    protected $trackingModeService;

    /**
     * @param AuthorizationCheckerInterface $auth
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(AuthorizationCheckerInterface $auth, TimesheetConfiguration $configuration, TrackingModeService $service)
    {
        $this->auth = $auth;
        $this->configuration = $configuration;
        $this->trackingModeService = $service;
    }

    /**
     * @param TimesheetEntity|mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetConstraint)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Timesheet');
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            return;
        }

        $this->validateBeginAndEnd($value, $this->context);
        $this->validateActivityAndProject($value, $this->context);
        $this->validatePermissions($value, $this->context);
    }

    /**
     * @param TimesheetEntity $timesheet
     * @param ExecutionContextInterface $context
     */
    protected function validatePermissions(TimesheetEntity $timesheet, ExecutionContextInterface $context)
    {
        // special case that would otherwise need to be validated in several controllers:
        // an entry is edited and the end date is removed (or duration deleted) would restart the record,
        // which might be disallowed for the current user
        if ($context->getViolations()->count() == 0 && null === $timesheet->getEnd()) {
            $mode = $this->trackingModeService->getActiveMode();
            $path = 'start';
            if ($mode->canEditEnd()) {
                $path = 'end';
            } elseif ($mode->canEditDuration()) {
                $path = 'duration';
            }
            if (!$this->auth->isGranted('start', $timesheet)) {
                $context->buildViolation('You are not allowed to start this timesheet record.')
                    ->atPath($path)
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::START_DISALLOWED)
                    ->addViolation();

                return;
            }
        }

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

        if (false === $this->configuration->isAllowFutureTimes()) {
            // allow configured default rounding time + 1 minute - see #1295
            $allowedDiff = ($this->configuration->getDefaultRoundingBegin() * 60) + 60;
            if ((time() + $allowedDiff) < $timesheet->getBegin()->getTimestamp()) {
                $context->buildViolation('The begin date cannot be in the future.')
                    ->atPath('begin')
                    ->setTranslationDomain('validators')
                    ->setCode(TimesheetConstraint::BEGIN_IN_FUTURE_ERROR)
                    ->addViolation();
            }
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

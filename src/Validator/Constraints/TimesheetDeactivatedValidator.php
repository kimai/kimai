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

/**
 * This validator is separate, so it can be easily deactivated in import scenarios.
 */
final class TimesheetDeactivatedValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetDeactivated)) {
            throw new UnexpectedTypeException($constraint, TimesheetDeactivated::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        $this->validateActivityAndProject($value, $this->context);
    }

    private function validateActivityAndProject(TimesheetEntity $timesheet, ExecutionContextInterface $context): void
    {
        $newOrStarted = $timesheet->isRunning() || $timesheet->getId() === null;

        if (!$newOrStarted) {
            return;
        }

        $activity = $timesheet->getActivity();
        if (null !== $activity && !$activity->isVisible()) {
            $context->buildViolation(TimesheetDeactivated::getErrorName(TimesheetDeactivated::DISABLED_ACTIVITY_ERROR))
                ->atPath('activity')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetDeactivated::DISABLED_ACTIVITY_ERROR)
                ->addViolation();
        }

        $project = $timesheet->getProject();
        if ($project === null) {
            return;
        }

        if (!$project->isVisible()) {
            $context->buildViolation(TimesheetDeactivated::getErrorName(TimesheetDeactivated::DISABLED_PROJECT_ERROR))
                ->atPath('project')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetDeactivated::DISABLED_PROJECT_ERROR)
                ->addViolation();
        }

        $customer = $project->getCustomer();
        if ($customer === null) {
            return;
        }

        if (!$customer->isVisible()) {
            $context->buildViolation(TimesheetDeactivated::getErrorName(TimesheetDeactivated::DISABLED_CUSTOMER_ERROR))
                ->atPath('customer')
                ->setTranslationDomain('validators')
                ->setCode(TimesheetDeactivated::DISABLED_CUSTOMER_ERROR)
                ->addViolation();
        }
    }
}

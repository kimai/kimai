<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator;

use App\Configuration\SystemConfiguration;
use App\Entity\Timesheet;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\RateServiceInterface;
use App\Utils\Duration;
use App\Utils\LocaleHelper;
use App\Validator\Constraints\TimesheetBudgetUsedConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetBudgetUsedValidator extends ConstraintValidator
{
    private $customerRepository;
    private $projectRepository;
    private $activityRepository;
    private $timesheetRepository;
    private $rateService;
    private $configuration;

    public function __construct(SystemConfiguration $configuration, CustomerRepository $customerRepository, ProjectRepository $projectRepository, ActivityRepository $activityRepository, TimesheetRepository $timesheetRepository, RateServiceInterface $rateService)
    {
        $this->configuration = $configuration;
        $this->customerRepository = $customerRepository;
        $this->projectRepository = $projectRepository;
        $this->activityRepository = $activityRepository;
        $this->timesheetRepository = $timesheetRepository;
        $this->rateService = $rateService;
    }

    /**
     * @param Timesheet $timesheet
     * @param Constraint $constraint
     */
    public function validate($timesheet, Constraint $constraint)
    {
        if (!($constraint instanceof TimesheetBudgetUsedConstraint)) {
            throw new UnexpectedTypeException($constraint, TimesheetBudgetUsedConstraint::class);
        }

        if (!\is_object($timesheet) || !($timesheet instanceof Timesheet)) {
            throw new UnexpectedTypeException($timesheet, Timesheet::class);
        }

        if ($this->configuration->isTimesheetAllowOverbookingBudget()) {
            return;
        }

        if ($this->context->getViolations()->count() > 0) {
            return;
        }

        // we can only work with stopped entries
        if (null === $timesheet->getEnd() || null === $timesheet->getUser() || null === $timesheet->getProject()) {
            return;
        }

        $duration = $timesheet->getDuration();
        if (null === $duration || 0 === $duration) {
            $duration = $timesheet->getEnd()->getTimestamp() - $timesheet->getBegin()->getTimestamp();
        }

        $timeRate = $this->rateService->calculate($timesheet);
        $rate = $timeRate->getRate();

        $activityDuration = $duration;
        $activityRate = $rate;
        $projectDuration = $duration;
        $projectRate = $rate;
        $customerDuration = $duration;
        $customerRate = $rate;

        if ($timesheet->getId() !== null) {
            $rawData = $this->timesheetRepository->getRawData($timesheet);

            // if an existing entry was updated, but duration and rate were not changed: do not validate
            // this could for example happen if overbooking config was recently activated
            if ($duration === $rawData['duration'] && $rate === $rawData['rate']) {
                return;
            }

            // the duration of an existing entry could be increased or lowered
            $activityId = (int) $rawData['activity'];
            $projectId = (int) $rawData['project'];
            $customerId = (int) $rawData['customer'];

            if (null !== $timesheet->getActivity() && $activityId === $timesheet->getActivity()->getId()) {
                $activityDuration -= $rawData['duration'];
                $activityRate -= $rawData['rate'];
            }

            if ($projectId === $timesheet->getProject()->getId()) {
                $projectDuration -= $rawData['duration'];
                $projectRate -= $rawData['rate'];
            }

            if ($customerId === $timesheet->getProject()->getCustomer()->getId()) {
                $customerDuration -= $rawData['duration'];
                $customerRate -= $rawData['rate'];
            }
        }

        if (null !== $timesheet->getActivity() && $this->checkActivity($constraint, $timesheet, $activityDuration, $activityRate)) {
            return;
        }

        if ($this->checkProject($constraint, $timesheet, $projectDuration, $projectRate)) {
            return;
        }

        if ($this->checkCustomer($constraint, $timesheet, $customerDuration, $customerRate)) {
            return;
        }
    }

    private function checkActivity(TimesheetBudgetUsedConstraint $constraint, Timesheet $timesheet, int $duration, float $rate): bool
    {
        $activity = $timesheet->getActivity();

        if (!$activity->hasBudget() && !$activity->hasTimeBudget()) {
            return false;
        }

        $stat = $this->activityRepository->getActivityStatistics($activity);

        $fullRate = ($stat->getRecordRate() + $rate);

        if ($activity->hasBudget() && $fullRate > $activity->getBudget()) {
            $this->addBudgetViolation($constraint, $timesheet, 'activity', $activity->getBudget(), $stat->getRecordRate());

            return true;
        }

        $fullDuration = ($stat->getRecordDuration() + $duration);

        if ($activity->hasTimeBudget() && $fullDuration > $activity->getTimeBudget()) {
            $this->addTimeBudgetViolation($constraint, 'activity', $activity->getTimeBudget(), $stat->getRecordDuration());

            return true;
        }

        return false;
    }

    private function checkProject(TimesheetBudgetUsedConstraint $constraint, Timesheet $timesheet, int $duration, float $rate): bool
    {
        $project = $timesheet->getProject();

        if (!$project->hasBudget() && !$project->hasTimeBudget()) {
            return false;
        }

        $stat = $this->projectRepository->getProjectStatistics($project);

        $fullRate = ($stat->getRecordRate() + $rate);

        if ($project->hasBudget() && $fullRate > $project->getBudget()) {
            $this->addBudgetViolation($constraint, $timesheet, 'project', $project->getBudget(), $stat->getRecordRate());

            return true;
        }

        $fullDuration = ($stat->getRecordDuration() + $duration);

        if ($project->hasTimeBudget() && $fullDuration > $project->getTimeBudget()) {
            $this->addTimeBudgetViolation($constraint, 'project', $project->getTimeBudget(), $stat->getRecordDuration());

            return true;
        }

        return false;
    }

    private function checkCustomer(TimesheetBudgetUsedConstraint $constraint, Timesheet $timesheet, int $duration, float $rate): bool
    {
        $customer = $timesheet->getProject()->getCustomer();

        if (!$customer->hasBudget() && !$customer->hasTimeBudget()) {
            return false;
        }

        $stat = $this->customerRepository->getCustomerStatistics($customer);

        $fullRate = ($stat->getRecordRate() + $rate);

        if ($customer->hasBudget() && $fullRate > $customer->getBudget()) {
            $this->addBudgetViolation($constraint, $timesheet, 'customer', $customer->getBudget(), $stat->getRecordRate());

            return true;
        }

        $fullDuration = ($stat->getRecordDuration() + $duration);

        if ($customer->hasTimeBudget() && $fullDuration > $customer->getTimeBudget()) {
            $this->addTimeBudgetViolation($constraint, 'customer', $customer->getTimeBudget(), $stat->getRecordDuration());

            return true;
        }

        return false;
    }

    private function addBudgetViolation(TimesheetBudgetUsedConstraint $constraint, Timesheet $timesheet, string $field, float $budget, float $rate)
    {
        // using the locale of the assigned user is not the best solution, but allows to be independent from the request stack
        $helper = new LocaleHelper($timesheet->getUser()->getLanguage());
        $currency = $timesheet->getProject()->getCustomer()->getCurrency();

        $free = $budget - $rate;
        $free = $free > 0 ? $free : 0;

        $this->context->buildViolation($constraint->messageRate)
            ->atPath($field)
            ->setTranslationDomain('validators')
            ->setParameters([
                '%used%' => $helper->money($rate, $currency),
                '%budget%' => $helper->money($budget, $currency),
                '%free%' => $helper->money($free, $currency)
            ])
            ->addViolation()
        ;
    }

    private function addTimeBudgetViolation(TimesheetBudgetUsedConstraint $constraint, string $field, int $budget, int $duration)
    {
        $durationFormat = new Duration();

        $free = $budget - $duration;
        $free = $free > 0 ? $free : 0;

        $this->context->buildViolation($constraint->messageTime)
            ->atPath($field)
            ->setTranslationDomain('validators')
            ->setParameters([
                '%used%' => $durationFormat->format($duration),
                '%budget%' => $durationFormat->format($budget),
                '%free%' => $durationFormat->format($free)
            ])
            ->addViolation()
        ;
    }
}

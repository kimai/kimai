<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\Activity\ActivityStatisticService;
use App\Configuration\LocaleService;
use App\Configuration\SystemConfiguration;
use App\Customer\CustomerStatisticService;
use App\Entity\Timesheet as TimesheetEntity;
use App\Model\BudgetStatisticModel;
use App\Project\ProjectStatisticService;
use App\Repository\TimesheetRepository;
use App\Timesheet\RateServiceInterface;
use App\Utils\Duration;
use App\Utils\LocaleFormatter;
use DateTime;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TimesheetBudgetUsedValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly CustomerStatisticService $customerStatisticService,
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly ActivityStatisticService $activityStatisticService,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly RateServiceInterface $rateService,
        private readonly AuthorizationCheckerInterface $security,
        private readonly LocaleService $localeService
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetBudgetUsed)) {
            throw new UnexpectedTypeException($constraint, TimesheetBudgetUsed::class);
        }

        if (!\is_object($value) || !($value instanceof TimesheetEntity)) {
            throw new UnexpectedTypeException($value, TimesheetEntity::class);
        }

        if ($this->configuration->isTimesheetAllowOverbookingBudget()) {
            return;
        }

        $begin = $value->getBegin();

        // we can only work with stopped entries
        if ($begin === null || $value->getEnd() === null || $value->getUser() === null) {
            return;
        }

        // budgets need only be calculated for billable records
        if (!$value->isBillable()) {
            return;
        }

        // this validator needs a project to calculate the rates
        if ($value->getProject() === null) {
            return;
        }

        $id = $value->getId();

        // when changing the date via the calendar and/or the API, the duration will not be reset by the
        // duration calculator (which runs after validation!) so we manually reset the duration before

        // ------------------------------------------------------------------------
        // Old solution (buggy) - duration MAY NOT BE RESET!
        // this will cause the timesheet to be deleted in the Weekly-QuickEntry-Flow
        // ------------------------------------------------------------------------
        // $timesheet->setDuration(null);
        // $duration = $timesheet->getDuration();

        // another possible solution is cloning the timesheet
        // $timesheet = clone $timesheet;
        // $timesheet->setDuration(null);
        // $duration = $timesheet->getDuration();

        $duration = $value->getCalculatedDuration();

        $timeRate = $this->rateService->calculate($value);
        $rate = $timeRate->getRate();

        $activityDuration = $duration ?? 0;
        $activityRate = $rate;
        $projectDuration = $duration ?? 0;
        $projectRate = $rate;
        $customerDuration = $duration ?? 0;
        $customerRate = $rate;
        $monthWasChanged = false;

        if ($id !== null) {
            $rawData = $this->timesheetRepository->getRawData($id);

            $activityId = (int) $rawData['activity'];
            $projectId = (int) $rawData['project'];
            $customerId = (int) $rawData['customer'];

            // if an existing entry was updated, but the relevant fields for budget calculation were not touched: do not validate!
            // this could for example happen when export flag is changed OR if "prevent overbooking"  config was recently activated and this is an old entry
            if ($duration === $rawData['duration'] &&
                $rate === $rawData['rate'] &&
                $value->isBillable() === $rawData['billable'] &&
                $begin->format('Y.m.d') === $rawData['begin']->format('Y.m.d') &&
                $value->getProject()->getId() === $projectId &&
                ($value->getActivity() === null || $value->getActivity()->getId() === $activityId)
            ) {
                return;
            }

            // the duration of an existing entry could be increased or lowered

            // only subtract the previously logged data in case the record was billable
            // if it wasn't billable, then its values are not included in the statistic models used later on
            if ($rawData['billable']) {
                if (null !== $value->getActivity() && $activityId === $value->getActivity()->getId()) {
                    $activityDuration -= $rawData['duration'];
                    $activityRate -= $rawData['rate'];
                }

                if (null !== $value->getProject()) {
                    if ($projectId === $value->getProject()->getId()) {
                        $projectDuration -= $rawData['duration'];
                        $projectRate -= $rawData['rate'];
                    }

                    if ($customerId === $value->getProject()->getCustomer()->getId()) {
                        $customerDuration -= $rawData['duration'];
                        $customerRate -= $rawData['rate'];
                    }
                }
            }

            $monthWasChanged = $begin->format('Y.m') !== $rawData['begin']->format('Y.m');
        }

        $now = new DateTime('now', $begin->getTimezone());
        $recordDate = $begin;

        if (null !== ($activity = $value->getActivity()) && $activity->hasBudgets()) {
            $dateTime = $activity->isMonthlyBudget() ? $recordDate : $now;
            if ($activity->isMonthlyBudget() && $monthWasChanged) {
                $activityDuration = $duration;
            }
            $stat = $this->activityStatisticService->getBudgetStatisticModel($activity, $dateTime);
            $this->checkBudgets($constraint, $stat, $value, $activityDuration, $activityRate, 'activity');
        }

        if (null !== ($project = $value->getProject())) {
            if ($project->hasBudgets()) {
                $dateTime = $project->isMonthlyBudget() ? $recordDate : $now;
                if ($project->isMonthlyBudget() && $monthWasChanged) {
                    $projectDuration = $duration;
                }
                $stat = $this->projectStatisticService->getBudgetStatisticModel($project, $dateTime);
                $this->checkBudgets($constraint, $stat, $value, $projectDuration, $projectRate, 'project');
            }
            if (null !== ($customer = $project->getCustomer()) && $customer->hasBudgets()) {
                $dateTime = $customer->isMonthlyBudget() ? $recordDate : $now;
                if ($customer->isMonthlyBudget() && $monthWasChanged) {
                    $customerDuration = $duration;
                }
                $stat = $this->customerStatisticService->getBudgetStatisticModel($customer, $dateTime);
                $this->checkBudgets($constraint, $stat, $value, $customerDuration, $customerRate, 'customer');
            }
        }
    }

    private function checkBudgets(TimesheetBudgetUsed $constraint, BudgetStatisticModel $stat, TimesheetEntity $timesheet, int $duration, float $rate, string $field): bool
    {
        $fullRate = ($stat->getBudgetSpent() + $rate);

        if ($stat->hasBudget() && $fullRate > $stat->getBudget()) {
            $this->addBudgetViolation($constraint, $timesheet, $field, $stat->getBudget(), $stat->getBudgetSpent());

            return true;
        }

        $fullDuration = ($stat->getTimeBudgetSpent() + $duration);

        if ($stat->hasTimeBudget() && $fullDuration > $stat->getTimeBudget()) {
            $this->addTimeBudgetViolation($constraint, $field, $stat->getTimeBudget(), $stat->getTimeBudgetSpent());

            return true;
        }

        return false;
    }

    private function addBudgetViolation(TimesheetBudgetUsed $constraint, TimesheetEntity $timesheet, string $field, float $budget, float $rate): void
    {
        // using the locale of the assigned user is not the best solution, but allows to be independent of the request stack
        $helper = new LocaleFormatter($this->localeService, $timesheet->getUser()?->getLocale() ?? 'en');
        $currency = $timesheet->getProject()->getCustomer()->getCurrency();

        $free = $budget - $rate;
        $free = max($free, 0);

        $message = $constraint->messageRate;
        if (!$this->security->isGranted('budget_money', $field)) {
            $message = $constraint->messagePermission;
        }

        $this->context->buildViolation($message)
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

    private function addTimeBudgetViolation(TimesheetBudgetUsed $constraint, string $field, int $budget, int $duration): void
    {
        $durationFormat = new Duration();

        $free = $budget - $duration;
        $free = max($free, 0);

        $message = $constraint->messageTime;
        if (!$this->security->isGranted('budget_time', $field)) {
            $message = $constraint->messagePermission;
        }

        $this->context->buildViolation($message)
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

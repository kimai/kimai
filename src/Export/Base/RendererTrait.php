<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Entity\ExportableItem;
use App\Model\TimesheetCountedStatistic;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;

trait RendererTrait
{
    /**
     * FIXME use statistic events to calculate budgets and do NOT iterate all results!
     *
     * @param ExportableItem[] $exportItems
     * @return array
     */
    protected function calculateSummary(array $exportItems): array
    {
        $summary = [];

        foreach ($exportItems as $exportItem) {
            $customerId = 'none';
            $projectId = 'none';
            $activityId = 'none';
            $userId = 'none';
            $customer = null;
            $project = null;
            $activity = null;
            $currency = null;

            if (null !== ($project = $exportItem->getProject())) {
                $customer = $project->getCustomer();
                $customerId = $customer->getId();
                $projectId = $project->getId();
                $currency = $customer->getCurrency();
            }

            if (null !== ($activity = $exportItem->getActivity())) {
                $activityId = $exportItem->getActivity()->getId();
            }

            if (null !== ($user = $exportItem->getUser())) {
                $userId = $user->getId();
            }

            $id = $customerId . '_' . $projectId;
            $type = $exportItem->getType();
            $category = $exportItem->getCategory();

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'customer' => '',
                    'customer_item' => null,
                    'project' => '',
                    'project_item' => null,
                    'activities' => [],
                    'currency' => $currency,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                    'type' => [],
                    'types' => [],
                    'users' => []
                ];

                if ($project !== null) {
                    $summary[$id]['customer'] = $customer->getName();
                    $summary[$id]['customer_item'] = $customer;
                    $summary[$id]['project'] = $project->getName();
                    $summary[$id]['project_item'] = $project;
                }
            }

            if (!isset($summary[$id]['type'][$type])) {
                $summary[$id]['type'][$type] = [
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }

            if (!isset($summary[$id]['types'][$type][$category])) {
                $summary[$id]['types'][$type][$category] = [
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }

            if (!isset($summary[$id]['activities'][$activityId])) {
                $summary[$id]['activities'][$activityId] = [
                    'activity' => '',
                    'currency' => $currency,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                    'users' => []
                ];

                if ($activity !== null) {
                    $summary[$id]['activities'][$activityId]['activity'] = $activity->getName();
                    $summary[$id]['activities'][$activityId]['activity_item'] = $activity;
                }
            }

            if (!isset($summary[$id]['users'][$userId])) {
                $summary[$id]['users'][$userId] = [
                    'user' => $user,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }
            if (!isset($summary[$id]['activities'][$activityId]['users'][$userId])) {
                $summary[$id]['activities'][$activityId]['users'][$userId] = [
                    'user' => $user,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }

            $duration = $exportItem->getDuration();
            if (null === $duration) {
                $duration = 0;
            }

            $rate = $exportItem->getRate();
            $internalRate = $exportItem->getInternalRate();

            // rate
            $summary[$id]['rate'] += $rate;
            $summary[$id]['type'][$type]['rate'] += $rate;
            $summary[$id]['types'][$type][$category]['rate'] += $rate;
            $summary[$id]['users'][$userId]['rate'] += $rate;
            $summary[$id]['activities'][$activityId]['rate'] += $rate;
            $summary[$id]['activities'][$activityId]['users'][$userId]['rate'] += $rate;

            // internal rate
            $summary[$id]['rate_internal'] += $internalRate;
            $summary[$id]['type'][$type]['rate_internal'] += $internalRate;
            $summary[$id]['types'][$type][$category]['rate_internal'] += $internalRate;
            $summary[$id]['users'][$userId]['rate_internal'] += $internalRate;
            $summary[$id]['activities'][$activityId]['rate_internal'] += $internalRate;
            $summary[$id]['activities'][$activityId]['users'][$userId]['rate_internal'] += $internalRate;

            // duration
            $summary[$id]['duration'] += $duration;
            $summary[$id]['type'][$type]['duration'] += $duration;
            $summary[$id]['types'][$type][$category]['duration'] += $duration;
            $summary[$id]['users'][$userId]['duration'] += $duration;
            $summary[$id]['activities'][$activityId]['duration'] += $duration;
            $summary[$id]['activities'][$activityId]['users'][$userId]['duration'] += $duration;
        }

        asort($summary);

        return $summary;
    }

    /**
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @param ProjectStatisticService $projectStatisticService
     * @return array
     */
    protected function calculateProjectBudget(array $exportItems, TimesheetQuery $query, ProjectStatisticService $projectStatisticService): array
    {
        $summary = [];
        $projects = [];
        $empty = new TimesheetCountedStatistic();

        foreach ($exportItems as $exportItem) {
            $customer = null;
            $project = null;
            $customerId = 'none';
            $projectId = 'none';

            if (null !== ($project = $exportItem->getProject())) {
                $customer = $project->getCustomer();
                $customerId = $customer->getId();
                $projectId = $project->getId();
                $projects[] = $project;
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'totals' => $empty->jsonSerialize(),
                    'time' => $project->getTimeBudget(),
                    'money' => $project->getBudget(),
                    'time_left' => null,
                    'money_left' => null,
                    'time_left_total' => null,
                    'money_left_total' => null,
                ];
            }
        }

        $today = $this->getToday($query);

        $allBudgets = $projectStatisticService->getBudgetStatisticModelForProjects($projects, $today);

        foreach ($allBudgets as $projectId => $statisticModel) {
            $project = $statisticModel->getProject();
            $id = $project->getCustomer()->getId() . '_' . $projectId;
            $total = $statisticModel->getStatisticTotal();
            $summary[$id]['totals'] = $total->jsonSerialize();
            if ($statisticModel->hasTimeBudget()) {
                $summary[$id]['time_left'] = $statisticModel->getTimeBudgetOpenRelative();
                $summary[$id]['time_left_total'] = $statisticModel->getTimeBudgetOpen();
            }
            if ($statisticModel->hasBudget()) {
                $summary[$id]['money_left'] = $statisticModel->getBudgetOpenRelative();
                $summary[$id]['money_left_total'] = $statisticModel->getBudgetOpen();
            }
        }

        return $summary;
    }

    private function getToday(TimesheetQuery $query): \DateTime
    {
        $end = $query->getEnd();

        if ($end !== null) {
            return $end;
        }

        if ($query->getCurrentUser() !== null) {
            $timezone = $query->getCurrentUser()->getTimezone();

            return new \DateTime('now', new \DateTimeZone($timezone));
        }

        if ($query->getUser() !== null) {
            $timezone = $query->getUser()->getTimezone();

            return new \DateTime('now', new \DateTimeZone($timezone));
        }

        return new \DateTime();
    }

    /**
     * @param ExportableItem[] $exportItems
     * @param TimesheetQuery $query
     * @param ActivityStatisticService $activityStatisticService
     * @return array
     */
    protected function calculateActivityBudget(array $exportItems, TimesheetQuery $query, ActivityStatisticService $activityStatisticService): array
    {
        $summary = [];
        $activities = [];
        $empty = new TimesheetCountedStatistic();

        foreach ($exportItems as $exportItem) {
            $customerId = 'none';
            $projectId = 'none';
            $project = null;
            $activity = null;

            if (null === ($activity = $exportItem->getActivity())) {
                continue;
            }

            if ($activity->isGlobal()) {
                continue;
            }

            $activities[] = $activity;

            if (null !== ($project = $exportItem->getProject())) {
                $projectId = $project->getId();
                $customerId = $project->getCustomer()->getId();
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [];
            }

            $activityId = $activity->getId();

            if (!isset($summary[$id][$activityId])) {
                $summary[$id][$activityId] = [
                    'totals' => $empty->jsonSerialize(),
                    'time' => $activity->getTimeBudget(),
                    'money' => $activity->getBudget(),
                    'time_left' => null,
                    'money_left' => null,
                    'time_left_total' => null,
                    'money_left_total' => null,
                ];
            }
        }

        $today = $this->getToday($query);

        $allBudgets = $activityStatisticService->getBudgetStatisticModelForActivities($activities, $today);

        foreach ($allBudgets as $activityId => $statisticModel) {
            $project = $statisticModel->getActivity()->getProject();
            $id = $project->getCustomer()->getId() . '_' . $project->getId();
            $total = $statisticModel->getStatisticTotal();
            $summary[$id][$activityId]['totals'] = $total->jsonSerialize();
            if ($statisticModel->hasTimeBudget()) {
                $summary[$id][$activityId]['time_left'] = $statisticModel->getTimeBudgetOpenRelative();
                $summary[$id][$activityId]['time_left_total'] = $statisticModel->getTimeBudgetOpen();
            }
            if ($statisticModel->hasBudget()) {
                $summary[$id][$activityId]['money_left'] = $statisticModel->getBudgetOpenRelative();
                $summary[$id][$activityId]['money_left_total'] = $statisticModel->getBudgetOpen();
            }
        }

        return $summary;
    }
}

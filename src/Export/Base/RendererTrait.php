<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Invoice\InvoiceItemInterface;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;

trait RendererTrait
{
    /**
     * @param InvoiceItemInterface[] $exportItems
     * @return array
     */
    protected function calculateSummary(array $exportItems)
    {
        $summary = [];

        foreach ($exportItems as $exportItem) {
            $customerId = 'none';
            $projectId = 'none';
            $activityId = 'none';
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

            $id = $customerId . '_' . $projectId;
            $type = $exportItem->getType();
            $category = $exportItem->getCategory();

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'customer' => '',
                    'project' => '',
                    'activities' => [],
                    'currency' => $currency,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                    'type' => [],
                    'types' => [],
                ];

                if ($project !== null) {
                    $summary[$id]['customer'] = $customer->getName();
                    $summary[$id]['project'] = $project->getName();
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
                ];

                if ($activity !== null) {
                    $summary[$id]['activities'][$activityId]['activity'] = $activity->getName();
                }
            }

            $duration = $exportItem->getDuration();
            if (null === $duration) {
                $duration = 0;
            }

            $summary[$id]['rate'] += $exportItem->getRate();
            $summary[$id]['type'][$type]['rate'] += $exportItem->getRate();
            $summary[$id]['types'][$type][$category]['rate'] += $exportItem->getRate();

            if (method_exists($exportItem, 'getInternalRate')) {
                $summary[$id]['rate_internal'] += $exportItem->getInternalRate();
                $summary[$id]['type'][$type]['rate_internal'] += $exportItem->getInternalRate();
                $summary[$id]['types'][$type][$category]['rate_internal'] += $exportItem->getInternalRate();
            } else {
                $summary[$id]['rate_internal'] += $exportItem->getRate();
                $summary[$id]['type'][$type]['rate_internal'] += $exportItem->getRate();
                $summary[$id]['types'][$type][$category]['rate_internal'] += $exportItem->getRate();
            }

            $summary[$id]['duration'] += $duration;
            $summary[$id]['type'][$type]['duration'] += $duration;
            $summary[$id]['types'][$type][$category]['duration'] += $duration;

            $summary[$id]['activities'][$activityId]['rate'] += $exportItem->getRate();
            $summary[$id]['activities'][$activityId]['duration'] += $duration;
        }

        asort($summary);

        return $summary;
    }

    /**
     * @param InvoiceItemInterface[] $exportItems
     * @param TimesheetQuery $query
     * @param ProjectStatisticService $projectStatisticService
     * @return array
     */
    protected function calculateProjectBudget(array $exportItems, TimesheetQuery $query, ProjectStatisticService $projectStatisticService)
    {
        $summary = [];
        $projects = [];

        foreach ($exportItems as $exportItem) {
            $customer = null;
            $project = null;
            $customerId = 'none';
            $projectId = 'none';

            if (null !== ($project = $exportItem->getProject())) {
                $customer = $project->getCustomer();
                $customerId = $customer->getId();
                $projectId = $project->getId();
                if ($project->hasBudgets()) {
                    $projects[] = $project;
                }
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'time' => $project->getTimeBudget(),
                    'money' => $project->getBudget(),
                    'time_left' => null,
                    'money_left' => null,
                ];
            }
        }

        $today = $this->getToday($query);

        $allBudgets = $projectStatisticService->getBudgetStatisticModelForProjects($projects, $today);

        foreach ($allBudgets as $projectId => $statisticModel) {
            $project = $statisticModel->getProject();
            $id = $project->getCustomer()->getId() . '_' . $projectId;
            if ($statisticModel->hasTimeBudget()) {
                $summary[$id]['time_left'] = $statisticModel->getTimeBudgetOpen();
            }
            if ($statisticModel->hasBudget()) {
                $summary[$id]['money_left'] = $statisticModel->getBudgetOpen();
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
     * @param InvoiceItemInterface[] $exportItems
     * @param TimesheetQuery $query
     * @param ActivityStatisticService $activityStatisticService
     * @return array
     */
    protected function calculateActivityBudget(array $exportItems, TimesheetQuery $query, ActivityStatisticService $activityStatisticService)
    {
        $summary = [];
        $activities = [];

        foreach ($exportItems as $exportItem) {
            $customerId = 'none';
            $projectId = 'none';
            $customer = null;
            $project = null;
            $activity = null;

            if (null === ($activity = $exportItem->getActivity())) {
                continue;
            }

            if ($activity->isGlobal()) {
                continue;
            }

            if ($activity->hasBudgets()) {
                $activities[] = $activity;
            }

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
                    'time' => $activity->getTimeBudget(),
                    'money' => $activity->getBudget(),
                    'time_left' => null,
                    'money_left' => null,
                ];
            }
        }

        $today = $this->getToday($query);

        $allBudgets = $activityStatisticService->getBudgetStatisticModelForActivities($activities, $today);

        foreach ($allBudgets as $activityId => $statisticModel) {
            $project = $statisticModel->getActivity()->getProject();
            $id = $project->getCustomer()->getId() . '_' . $project->getId();
            if ($statisticModel->hasTimeBudget()) {
                $summary[$id][$activityId]['time_left'] = $statisticModel->getTimeBudgetOpen();
            }
            if ($statisticModel->hasBudget()) {
                $summary[$id][$activityId]['money_left'] = $statisticModel->getBudgetOpen();
            }
        }

        return $summary;
    }
}

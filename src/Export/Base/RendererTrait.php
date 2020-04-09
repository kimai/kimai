<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Export\ExportItemInterface;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;

trait RendererTrait
{
    /**
     * @param ExportItemInterface[] $exportItems
     * @return array
     */
    protected function calculateSummary(array $exportItems)
    {
        $summary = [];

        foreach ($exportItems as $exportItem) {
            $customerId = 'none';
            $customerName = '';
            $currency = null;
            $projectId = 'none';
            $projectName = '';
            $activityId = 'none';
            $activityName = '';

            if (null !== $exportItem->getProject()) {
                $customerId = $exportItem->getProject()->getCustomer()->getId();
                $customerName = $exportItem->getProject()->getCustomer()->getName();
                $projectId = $exportItem->getProject()->getId();
                $projectName = $exportItem->getProject()->getName();
                $currency = $exportItem->getProject()->getCustomer()->getCurrency();
            }

            if (null !== $exportItem->getActivity()) {
                $activityId = $exportItem->getActivity()->getId();
                $activityName = $exportItem->getActivity()->getName();
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'customer' => $customerName,
                    'project' => $projectName,
                    'activities' => [],
                    'currency' => $currency,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }

            if (!isset($summary[$id]['activities'][$activityId])) {
                $summary[$id]['activities'][$activityId] = [
                    'activity' => $activityName,
                    'currency' => $currency,
                    'rate' => 0,
                    'rate_internal' => 0,
                    'duration' => 0,
                ];
            }

            $duration = $exportItem->getDuration();
            if (null === $duration) {
                $duration = 0;
            }

            $summary[$id]['rate'] += $exportItem->getRate();
            if (method_exists($exportItem, 'getInternalRate')) {
                $summary[$id]['rate_internal'] += $exportItem->getInternalRate();
            } else {
                $summary[$id]['rate_internal'] += $exportItem->getRate();
            }
            $summary[$id]['duration'] += $duration;
            $summary[$id]['activities'][$activityId]['rate'] += $exportItem->getRate();
            $summary[$id]['activities'][$activityId]['duration'] += $duration;
        }

        asort($summary);

        return $summary;
    }

    /**
     * @param ExportItemInterface[] $exportItems
     * @param TimesheetQuery $query
     * @param ProjectRepository $projectRepository
     * @return array
     */
    protected function calculateProjectBudget(array $exportItems, TimesheetQuery $query, ProjectRepository $projectRepository)
    {
        $summary = [];

        foreach ($exportItems as $exportItem) {
            $customer = null;
            $customerId = 'none';
            $project = null;
            $projectId = 'none';

            if (null !== ($project = $exportItem->getProject())) {
                $customer = $project->getCustomer();
                $customerId = $customer->getId();
                $projectId = $project->getId();
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'time' => $project->getTimeBudget(),
                    'money' => $project->getBudget(),
                    'time_left' => null,
                    'money_left' => null,
                ];

                if (null !== $project && ($project->getTimeBudget() > 0 || $project->getBudget() > 0)) {
                    $projectStats = $projectRepository->getProjectStatistics($project, null, $query->getEnd());

                    if ($project->getTimeBudget() > 0) {
                        $summary[$id]['time_left'] = $project->getTimeBudget() - $projectStats->getRecordDuration();
                    }
                    if ($project->getBudget() > 0) {
                        $summary[$id]['money_left'] = $project->getBudget() - $projectStats->getRecordRate();
                    }
                }
            }
        }

        return $summary;
    }
}

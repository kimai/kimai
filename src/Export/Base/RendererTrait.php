<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Export\ExportItemInterface;

trait RendererTrait
{
    /**
     * @param ExportItemInterface[] $timesheets
     * @return array
     */
    protected function calculateSummary(array $timesheets)
    {
        $summary = [];

        foreach ($timesheets as $timesheet) {
            $customerId = 'none';
            $customerName = '';
            $currency = null;
            $projectId = 'none';
            $projectName = '';
            $activityId = 'none';
            $activityName = '';

            if (null !== $timesheet->getProject()) {
                $customerId = $timesheet->getProject()->getCustomer()->getId();
                $customerName = $timesheet->getProject()->getCustomer()->getName();
                $projectId = $timesheet->getProject()->getId();
                $projectName = $timesheet->getProject()->getName();
                $currency = $timesheet->getProject()->getCustomer()->getCurrency();
            }

            if (null !== $timesheet->getActivity()) {
                $activityId = $timesheet->getActivity()->getId();
                $activityName = $timesheet->getActivity()->getName();
            }

            $id = $customerId . '_' . $projectId;

            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'customer' => $customerName,
                    'project' => $projectName,
                    'activities' => [],
                    'currency' => $currency,
                    'rate' => 0,
                    'duration' => 0,
                ];
            }

            if (!isset($summary[$id]['activities'][$activityId])) {
                $summary[$id]['activities'][$activityId] = [
                    'activity' => $activityName,
                    'currency' => $currency,
                    'rate' => 0,
                    'duration' => 0,
                ];
            }

            $duration = $timesheet->getDuration();
            if (null === $duration) {
                $duration = 0;
            }

            $summary[$id]['rate'] += $timesheet->getRate();
            $summary[$id]['duration'] += $duration;
            $summary[$id]['activities'][$activityId]['rate'] += $timesheet->getRate();
            $summary[$id]['activities'][$activityId]['duration'] += $duration;
        }

        asort($summary);

        return $summary;
    }
}

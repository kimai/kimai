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

            $duration = $exportItem->getDuration();
            if (null === $duration) {
                $duration = 0;
            }

            $summary[$id]['rate'] += $exportItem->getRate();
            $summary[$id]['duration'] += $duration;
            $summary[$id]['activities'][$activityId]['rate'] += $exportItem->getRate();
            $summary[$id]['activities'][$activityId]['duration'] += $duration;
        }

        asort($summary);

        return $summary;
    }
}

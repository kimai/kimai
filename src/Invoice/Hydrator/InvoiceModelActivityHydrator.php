<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Activity\ActivityStatisticService;
use App\Entity\Activity;
use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

final class InvoiceModelActivityHydrator implements InvoiceModelHydrator
{
    use BudgetHydratorTrait;

    public function __construct(private readonly ActivityStatisticService $activityStatistic)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $activities = [];

        foreach ($model->getEntries() as $entry) {
            if ($entry->getActivity() === null) {
                continue;
            }

            $key = 'A_' . $entry->getActivity()->getId();
            if (!\array_key_exists($key, $activities)) {
                $activities[$key] = $entry->getActivity();
            }
        }

        if (\count($activities) === 0) {
            return [];
        }

        $activities = array_values($activities);

        $values = [];
        $i = 0;

        foreach ($activities as $activity) {
            $prefix = '';
            if ($i > 0) {
                $prefix = $i . '.';
            }
            $values = array_merge($values, $this->getValuesFromActivity($model, $activity, $prefix));
            $i++;
        }

        return $values;
    }

    private function getValuesFromActivity(InvoiceModel $model, Activity $activity, string $prefix): array
    {
        $prefix = 'activity.' . $prefix;

        $values = [
            $prefix . 'id' => $activity->getId(),
            $prefix . 'name' => $activity->getName() ?? '',
            $prefix . 'comment' => $activity->getComment() ?? '',
            $prefix . 'number' => $activity->getNumber() ?? '',
            $prefix . 'invoice_text' => $activity->getInvoiceText() ?? '',
        ];

        $end = $model->getQuery()?->getEnd();
        if ($end !== null) {
            $statistic = $this->activityStatistic->getBudgetStatisticModel($activity, $end);

            $values = array_merge($values, $this->getBudgetValues($prefix, $statistic, $model));
        }

        foreach ($activity->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

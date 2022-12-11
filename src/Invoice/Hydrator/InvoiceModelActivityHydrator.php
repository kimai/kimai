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

    public function __construct(private ActivityStatisticService $activityStatistic)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        if (!$model->getQuery()->hasActivities()) {
            return [];
        }

        $values = [];
        $i = 0;

        $activities = $model->getQuery()->getActivities();
        if (\count($activities) === 1) {
            $values['activity'] = $activities[0]->getName();
        }

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
            $prefix . 'name' => $activity->getName(),
            $prefix . 'comment' => $activity->getComment(),
        ];

        $statistic = $this->activityStatistic->getBudgetStatisticModel($activity, $model->getQuery()->getEnd());

        $values = array_merge($values, $this->getBudgetValues($prefix, $statistic, $model));

        foreach ($activity->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

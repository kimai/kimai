<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelActivityHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        $activity = $model->getQuery()->getActivity();
        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        if (null === $activity) {
            return [];
        }

        $values = [
            'activity.id' => $activity->getId(),
            'activity.name' => $activity->getName(),
            'activity.comment' => $activity->getComment(),
            'activity.fixed_rate' => $formatter->getFormattedMoney($activity->getFixedRate(), $currency),
            'activity.fixed_rate_nc' => $formatter->getFormattedMoney($activity->getFixedRate(), null),
            'activity.fixed_rate_plain' => $activity->getFixedRate(),
            'activity.hourly_rate' => $formatter->getFormattedMoney($activity->getHourlyRate(), $currency),
            'activity.hourly_rate_nc' => $formatter->getFormattedMoney($activity->getHourlyRate(), null),
            'activity.hourly_rate_plain' => $activity->getHourlyRate(),
        ];

        foreach ($activity->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                'activity.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

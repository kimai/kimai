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

        if (null === $activity) {
            return [];
        }

        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        $values = [
            'activity.id' => $activity->getId(),
            'activity.name' => $activity->getName(),
            'activity.comment' => $activity->getComment(),
        ];

        foreach ($activity->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                'activity.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

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

class InvoiceModelProjectHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        $project = $model->getQuery()->getProject();

        if (null === $project) {
            return [];
        }

        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        $values = [
            'project.id' => $project->getId(),
            'project.name' => $project->getName(),
            'project.comment' => $project->getComment(),
            'project.order_number' => $project->getOrderNumber(),
            'project.start_date' => null !== $project->getStart() ? $formatter->getFormattedDateTime($project->getStart()) : '',
            'project.end_date' => null !== $project->getEnd() ? $formatter->getFormattedDateTime($project->getEnd()) : '',
            'project.order_date' => null !== $project->getOrderDate() ? $formatter->getFormattedDateTime($project->getOrderDate()) : '',
            'project.fixed_rate' => $formatter->getFormattedMoney($project->getFixedRate(), $currency),
            'project.fixed_rate_nc' => $formatter->getFormattedMoney($project->getFixedRate(), null),
            'project.fixed_rate_plain' => $project->getFixedRate(),
            'project.hourly_rate' => $formatter->getFormattedMoney($project->getHourlyRate(), $currency),
            'project.hourly_rate_nc' => $formatter->getFormattedMoney($project->getHourlyRate(), null),
            'project.hourly_rate_plain' => $project->getHourlyRate(),
            'project.budget_money' => $formatter->getFormattedMoney($project->getBudget(), $currency),
            'project.budget_money_nc' => $formatter->getFormattedMoney($project->getBudget(), null),
            'project.budget_money_plain' => $project->getBudget(),
            'project.budget_time' => $project->getTimeBudget(),
            'project.budget_time_decimal' => $formatter->getFormattedDecimalDuration($project->getTimeBudget()),
            'project.budget_time_minutes' => number_format($project->getTimeBudget() / 60),
        ];

        foreach ($project->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                'project.meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

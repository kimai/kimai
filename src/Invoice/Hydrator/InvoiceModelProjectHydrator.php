<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Entity\Project;
use App\Invoice\InvoiceFormatter;
use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;

class InvoiceModelProjectHydrator implements InvoiceModelHydrator
{
    public function hydrate(InvoiceModel $model): array
    {
        if (!$model->getQuery()->hasProjects()) {
            return [];
        }

        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        $values = [];
        $i = 0;

        foreach ($model->getQuery()->getProjects() as $project) {
            $prefix = '';
            if ($i > 0) {
                $prefix = $i . '.';
            }
            $values = array_merge($values, $this->getValuesFromProject($project, $formatter, $currency, $prefix));
            $i++;
        }

        return $values;
    }

    private function getValuesFromProject(Project $project, InvoiceFormatter $formatter, string $currency, string $prefix): array
    {
        $prefix = 'project.' . $prefix;

        $values = [
            $prefix . 'id' => $project->getId(),
            $prefix . 'name' => $project->getName(),
            $prefix . 'comment' => $project->getComment(),
            $prefix . 'order_number' => $project->getOrderNumber(),
            $prefix . 'start_date' => null !== $project->getStart() ? $formatter->getFormattedDateTime($project->getStart()) : '',
            $prefix . 'end_date' => null !== $project->getEnd() ? $formatter->getFormattedDateTime($project->getEnd()) : '',
            $prefix . 'order_date' => null !== $project->getOrderDate() ? $formatter->getFormattedDateTime($project->getOrderDate()) : '',
            $prefix . 'budget_money' => $formatter->getFormattedMoney($project->getBudget(), $currency),
            $prefix . 'budget_money_nc' => $formatter->getFormattedMoney($project->getBudget(), null),
            $prefix . 'budget_money_plain' => $project->getBudget(),
            $prefix . 'budget_time' => $project->getTimeBudget(),
            $prefix . 'budget_time_decimal' => $formatter->getFormattedDecimalDuration($project->getTimeBudget()),
            $prefix . 'budget_time_minutes' => number_format($project->getTimeBudget() / 60),
        ];

        foreach ($project->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

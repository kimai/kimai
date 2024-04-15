<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Entity\Project;
use App\Invoice\InvoiceModel;
use App\Invoice\InvoiceModelHydrator;
use App\Project\ProjectStatisticService;

final class InvoiceModelProjectHydrator implements InvoiceModelHydrator
{
    use BudgetHydratorTrait;

    public function __construct(private ProjectStatisticService $projectStatistic)
    {
    }

    public function hydrate(InvoiceModel $model): array
    {
        $projects = [];

        foreach ($model->getEntries() as $entry) {
            if ($entry->getProject() === null) {
                continue;
            }

            $key = 'P_' . $entry->getProject()->getId();
            if (!\array_key_exists($key, $projects)) {
                $projects[$key] = $entry->getProject();
            }
        }

        if (\count($projects) === 0) {
            return [];
        }

        $projects = array_values($projects);

        $values = [];
        $i = 0;

        foreach ($projects as $project) {
            $prefix = '';
            if ($i > 0) {
                $prefix = $i . '.';
            }
            $values = array_merge($values, $this->getValuesFromProject($model, $project, $prefix));
            $i++;
        }

        return $values;
    }

    private function getValuesFromProject(InvoiceModel $model, Project $project, string $prefix): array
    {
        $prefix = 'project.' . $prefix;

        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        $values = [
            $prefix . 'id' => $project->getId(),
            $prefix . 'name' => $project->getName() ?? '',
            $prefix . 'comment' => $project->getComment() ?? '',
            $prefix . 'order_number' => $project->getOrderNumber(),
            $prefix . 'start_date' => null !== $project->getStart() ? $formatter->getFormattedDateTime($project->getStart()) : '',
            $prefix . 'end_date' => null !== $project->getEnd() ? $formatter->getFormattedDateTime($project->getEnd()) : '',
            $prefix . 'order_date' => null !== $project->getOrderDate() ? $formatter->getFormattedDateTime($project->getOrderDate()) : '',
            $prefix . 'budget_money' => $formatter->getFormattedMoney($project->getBudget(), $currency),
            $prefix . 'budget_money_nc' => $formatter->getFormattedMoney($project->getBudget(), $currency, false),
            $prefix . 'budget_money_plain' => $project->getBudget(),
            $prefix . 'budget_time' => $project->getTimeBudget(),
            $prefix . 'budget_time_decimal' => $formatter->getFormattedDecimalDuration($project->getTimeBudget()),
            $prefix . 'budget_time_minutes' => (int) ($project->getTimeBudget() / 60),
            $prefix . 'number' => $project->getNumber() ?? '',
            $prefix . 'invoice_text' => $project->getInvoiceText() ?? '',
        ];

        if ($model->getQuery()?->getEnd() !== null) {
            $statistic = $this->projectStatistic->getBudgetStatisticModel($project, $model->getQuery()->getEnd());

            $values = array_merge($values, $this->getBudgetValues($prefix, $statistic, $model));
        }

        foreach ($project->getMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

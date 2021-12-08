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

class InvoiceModelProjectHydrator implements InvoiceModelHydrator
{
    private $projectStatistic;

    public function __construct(ProjectStatisticService $projectStatistic)
    {
        $this->projectStatistic = $projectStatistic;
    }

    public function hydrate(InvoiceModel $model): array
    {
        if (!$model->getQuery()->hasProjects()) {
            return [];
        }

        $values = [];
        $i = 0;

        if (\count($model->getQuery()->getProjects()) === 1) {
            $values['project'] = $model->getQuery()->getProjects()[0]->getName();
        }

        foreach ($model->getQuery()->getProjects() as $project) {
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

        $values = [
            $prefix . 'id' => $project->getId(),
            $prefix . 'name' => $project->getName(),
            $prefix . 'comment' => $project->getComment(),
            $prefix . 'order_number' => $project->getOrderNumber(),
            $prefix . 'start_date' => null !== $project->getStart() ? $formatter->getFormattedDateTime($project->getStart()) : '',
            $prefix . 'end_date' => null !== $project->getEnd() ? $formatter->getFormattedDateTime($project->getEnd()) : '',
            $prefix . 'order_date' => null !== $project->getOrderDate() ? $formatter->getFormattedDateTime($project->getOrderDate()) : '',
        ];

        $statistic = $this->projectStatistic->getBudgetStatisticModel($project, $model->getQuery()->getEnd());
        $currency = $model->getCurrency();
        $formatter = $model->getFormatter();

        if ($model->getTemplate()->isDecimalDuration()) {
            $budgetOpenDuration = $formatter->getFormattedDecimalDuration($statistic->getTimeBudgetOpen());
        } else {
            $budgetOpenDuration = $formatter->getFormattedDuration($statistic->getTimeBudgetOpen());
        }

        $values = array_merge($values, [
            $prefix . 'budget_open' => $formatter->getFormattedMoney($statistic->getBudgetOpen(), $currency),
            $prefix . 'budget_open_plain' => $statistic->getBudgetOpen(),
            $prefix . 'time_budget_open' => $budgetOpenDuration,
            $prefix . 'time_budget_open_plain' => $statistic->getTimeBudgetOpen(),
        ]);

        foreach ($project->getVisibleMetaFields() as $metaField) {
            $values = array_merge($values, [
                $prefix . 'meta.' . $metaField->getName() => $metaField->getValue(),
            ]);
        }

        return $values;
    }
}

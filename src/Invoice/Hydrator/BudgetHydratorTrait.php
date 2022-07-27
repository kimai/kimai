<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Hydrator;

use App\Invoice\InvoiceModel;
use App\Model\BudgetStatisticModel;

trait BudgetHydratorTrait
{
    protected function getBudgetValues(string $prefix, BudgetStatisticModel $statistic, InvoiceModel $model): array
    {
        $formatter = $model->getFormatter();
        $currency = $model->getCurrency();

        $budgetOpen = $statistic->getBudgetOpenRelative();
        $budgetTimeOpen = $statistic->getTimeBudgetOpenRelative();
        $budgetOpenDuration = $formatter->getFormattedDecimalDuration($budgetTimeOpen);

        return [
            $prefix . 'budget_open' => $formatter->getFormattedMoney($budgetOpen, $currency),
            $prefix . 'budget_open_plain' => $budgetOpen,
            $prefix . 'time_budget_open' => $budgetOpenDuration,
            $prefix . 'time_budget_open_plain' => $budgetTimeOpen,
        ];
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Repository\Query\InvoiceQuery;
use App\Timesheet\RateCalculator\RateCalculatorMode;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class InvoiceModelFactory
{
    /**
     * @param iterable<InvoiceModelHydrator> $modelHydrators
     * @param iterable<InvoiceItemHydrator> $itemHydrators
     */
    public function __construct(
        private readonly RateCalculatorMode $rateCalculatorMode,
        #[TaggedIterator(InvoiceModelHydrator::class)]
        private readonly iterable $modelHydrators,
        #[TaggedIterator(InvoiceItemHydrator::class)]
        private readonly iterable $itemHydrators,
    ) {
    }

    public function createModel(InvoiceFormatter $formatter, Customer $customer, InvoiceTemplate $template, InvoiceQuery $query): InvoiceModel
    {
        $model = new InvoiceModel($formatter, $customer, $template, $this->rateCalculatorMode);
        foreach ($this->modelHydrators as $modelHydrator) {
            $model->addModelHydrator($modelHydrator);
        }
        foreach ($this->itemHydrators as $itemHydrator) {
            $model->addItemHydrator($itemHydrator);
        }

        $model->setQuery($query);

        return $model;
    }
}

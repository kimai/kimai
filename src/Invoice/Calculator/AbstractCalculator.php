<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Invoice\InvoiceItem;
use App\Invoice\InvoiceModel;
use App\Invoice\TaxRow;

abstract class AbstractCalculator
{
    protected InvoiceModel $model;
    /**
     * @var array InvoiceItem[]
     */
    private array $cached = [];

    /**
     * TODO make this method abstract in 3.0
     *
     * @return InvoiceItem[]
     */
    protected function calculateEntries(): array
    {
        return [];
    }

    /**
     * TODO make this method final in 3.0
     *
     * @return InvoiceItem[]
     */
    public function getEntries(): array
    {
        if (\count($this->cached) === 0) {
            $this->cached[] = $this->calculateEntries();
        }

        return $this->cached;
    }

    /**
     * @param array<InvoiceItem> $items
     * @return array<InvoiceItem>
     */
    protected function sortEntries(array $items): array
    {
        usort($items, function (InvoiceItem $item1, InvoiceItem $item2) {
            return $item1->getBegin() <=> $item2->getBegin();
        });

        return $items;
    }

    abstract public function getId(): string;

    public function setModel(InvoiceModel $model): void
    {
        $this->model = $model;
    }

    public function getSubtotal(): float
    {
        $amount = 0.00;
        foreach ($this->model->getEntries() as $entry) {
            $amount += $entry->getRate();
        }

        return round($amount, 2);
    }

    /**
     * @deprecated use getTaxRows() instead
     */
    public function getVat(): float
    {
        return $this->model->getTemplate()->getVat() ?? 0.00;
    }

    /**
     * @return array<TaxRow>
     */
    public function getTaxRows(): array
    {
        $rows = [];
        foreach ($this->model->getTemplate()->getTaxRates() as $taxRate) {
            $rows[] = new TaxRow($taxRate, $this->getSubtotal());
        }

        return $rows;
    }

    public function getTax(): float
    {
        $tax = 0.00;
        foreach ($this->getTaxRows() as $row) {
            $tax += $row->getAmount();
        }

        return round($tax, 2);
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax();
    }

    /**
     * Returns the total amount of worked time in seconds.
     */
    public function getTimeWorked(): int
    {
        $time = 0;
        foreach ($this->model->getEntries() as $entry) {
            if (null !== $entry->getDuration()) {
                $time += $entry->getDuration();
            }
        }

        return $time;
    }
}

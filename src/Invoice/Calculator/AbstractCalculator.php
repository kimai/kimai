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
     * @var InvoiceItem[]
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
            foreach ($this->calculateEntries() as $entry) {
                if (!$entry->isFixedRate() && $entry->getHourlyRate() !== null && $entry->getHourlyRate() > 0) {
                    $entry->setDuration($this->model->getRateCalculatorMode()->roundDuration($entry->getDuration()));
                    // when merging many entries, we might run into rounding issues
                    // so we have to recalculate the hourly rate here
                    $entry->setRate($this->model->getRateCalculatorMode()->calculateRate($entry->getHourlyRate(), $entry->getDuration()));
                }

                $this->cached[] = $entry;
            }
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
        // using the entries and not the raw data, so we make sure to use the same base for everything
        foreach ($this->getEntries() as $entry) {
            $amount += $entry->getRate();
        }

        return round($amount, 2, PHP_ROUND_HALF_UP);
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

        return round($tax, 2, PHP_ROUND_HALF_UP);
    }

    public function getTotal(): float
    {
        return round($this->getSubtotal() + $this->getTax(), 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Returns the total amount of worked time in seconds.
     */
    public function getTimeWorked(): int
    {
        $time = 0;
        // using the entries and not the raw data, so we make sure to use the same base for everything
        foreach ($this->getEntries() as $entry) {
            if (null !== $entry->getDuration()) {
                $time += $entry->getDuration();
            }
        }

        return $time;
    }
}

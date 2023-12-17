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

abstract class AbstractCalculator
{
    protected InvoiceModel $model;

    /**
     * @return InvoiceItem[]
     */
    abstract public function getEntries(): array;

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

    public function getVat(): float
    {
        return $this->model->getTemplate()->getVat() ?? 0.00;
    }

    public function getTax(): float
    {
        $vat = $this->getVat();
        if (0.00 === $vat) {
            return 0.00;
        }

        $percent = $vat / 100.00;

        return round($this->getSubtotal() * $percent, 2);
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax();
    }

    /**
     * Returns the total amount of worked time in seconds.
     *
     * @return int
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

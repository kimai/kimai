<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\Timesheet;
use App\Model\InvoiceModel;

abstract class AbstractCalculator
{
    /**
     * @var string
     */
    protected $currency;

    /**
     * @var InvoiceModel
     */
    protected $model;

    /**
     * @return Timesheet[]
     */
    abstract public function getEntries();

    /**
     * @return string
     */
    abstract public function getId(): string;

    /**
     * @param InvoiceModel $model
     */
    public function setModel(InvoiceModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return float
     */
    public function getSubtotal(): float
    {
        $amount = 0;
        foreach ($this->model->getEntries() as $entry) {
            $amount += $entry->getRate();
        }

        return round($amount, 2);
    }

    /**
     * @return float
     */
    public function getVat(): ?float
    {
        return $this->model->getTemplate()->getVat();
    }

    /**
     * @return float
     */
    public function getTax(): float
    {
        $vat = $this->getVat();
        if (0 == $vat) {
            return 0;
        }

        $percent = $vat / 100.00;

        return round($this->getSubtotal() * $percent, 2);
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax();
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->model->getCustomer()->getCurrency();
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
            $time += $entry->getDuration();
        }

        return $time;
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Timesheet;
use App\Model\InvoiceModel;

/**
 * Class DefaultCalculator works on all given entries using:
 * - the customers currency
 * - the invoice template vat rate
 * - the entries rate
 */
class DefaultCalculator implements CalculatorInterface
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
    public function getEntries()
    {
        return $this->model->getEntries();
    }

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
    public function getVat()
    {
        return $this->model->getTemplate()->getVat();
    }

    /**
     * @return float
     */
    public function getTax(): float
    {
        $vat = $this->getVat();
        if ($vat == 0) {
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
}

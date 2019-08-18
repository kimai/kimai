<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\Timesheet;
use App\Invoice\InvoiceItem;

abstract class AbstractMergedCalculator extends AbstractCalculator
{
    private $throwExceptionOnInvalidEntry = false;

    protected function setThrowExceptionOnInvalidMerge(bool $throwException)
    {
        $this->throwExceptionOnInvalidEntry = $throwException;
    }

    protected function mergeTimesheets(InvoiceItem $invoiceItem, Timesheet $entry)
    {
        $invoiceItem->setAmount($invoiceItem->getAmount() + 1);
        $invoiceItem->setUser($entry->getUser());
        $invoiceItem->setRate($invoiceItem->getRate() + $entry->getRate());
        $invoiceItem->setDuration($invoiceItem->getDuration() + $entry->getDuration());

        if (null !== $entry->getFixedRate()) {
            if (null !== $invoiceItem->getFixedRate() && $invoiceItem->getFixedRate() !== $entry->getFixedRate()) {
                if ($this->throwExceptionOnInvalidEntry) {
                    throw new \InvalidArgumentException('Cannot mix different fixed-rates');
                }
            }
            $invoiceItem->setFixedRate($entry->getFixedRate());
        }

        if (null !== $entry->getHourlyRate()) {
            if (null !== $invoiceItem->getHourlyRate() && $invoiceItem->getHourlyRate() !== $entry->getHourlyRate()) {
                if ($this->throwExceptionOnInvalidEntry) {
                    throw new \InvalidArgumentException('Cannot mix different hourly-rates');
                }
            }
            $invoiceItem->setHourlyRate($entry->getHourlyRate());
        }

        if (null === $invoiceItem->getBegin() || $invoiceItem->getBegin()->getTimestamp() > $entry->getBegin()->getTimestamp()) {
            $invoiceItem->setBegin($entry->getBegin());
        }

        if (null === $invoiceItem->getEnd() || $invoiceItem->getEnd()->getTimestamp() < $entry->getEnd()->getTimestamp()) {
            $invoiceItem->setEnd($entry->getEnd());
        }

        if (null !== $this->model->getQuery()->getActivity()) {
            $invoiceItem->setActivity($this->model->getQuery()->getActivity());
            $invoiceItem->setDescription($this->model->getQuery()->getActivity()->getName());
        } elseif (null !== $this->model->getQuery()->getProject()) {
            $invoiceItem->setDescription($this->model->getQuery()->getProject()->getName());
        }

        if (null === $invoiceItem->getActivity()) {
            $invoiceItem->setActivity($entry->getActivity());
        }

        if (null === $invoiceItem->getProject()) {
            $invoiceItem->setProject($entry->getProject());
        }

        if (empty($invoiceItem->getDescription())) {
            $invoiceItem->setDescription($entry->getActivity()->getName());
        }
    }
}

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
use App\Invoice\InvoiceItemInterface;
use App\Invoice\InvoiceItemWithAmountInterface;

abstract class AbstractMergedCalculator extends AbstractCalculator
{
    public const TYPE_MIXED = 'mixed';
    public const CATEGORY_MIXED = 'mixed';

    /**
     * @deprecated since 1.3 - will be removed with 2.0
     */
    protected function mergeTimesheets(InvoiceItem $invoiceItem, Timesheet $entry)
    {
        @trigger_error('mergeTimesheets() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

        $this->mergeInvoiceItems($invoiceItem, $entry);
    }

    protected function mergeInvoiceItems(InvoiceItem $invoiceItem, InvoiceItemInterface $entry)
    {
        $duration = $invoiceItem->getDuration();
        if (null !== $entry->getDuration()) {
            $duration += $entry->getDuration();
        }

        $amount = 1;
        if ($entry instanceof InvoiceItemWithAmountInterface) {
            $amount = $entry->getAmount();
        }

        $type = $entry->getType();
        $category = $entry->getCategory();

        if (null !== $invoiceItem->getType() && $type !== $invoiceItem->getType()) {
            $type = self::TYPE_MIXED;
        }
        if (null !== $invoiceItem->getCategory() && $category !== $invoiceItem->getCategory()) {
            $category = self::CATEGORY_MIXED;
        }

        $invoiceItem->setType($type);
        $invoiceItem->setCategory($category);

        $invoiceItem->setAmount($invoiceItem->getAmount() + $amount);
        $invoiceItem->setUser($entry->getUser());
        $invoiceItem->setRate($invoiceItem->getRate() + $entry->getRate());
        if (method_exists($entry, 'getInternalRate')) {
            $invoiceItem->setInternalRate($invoiceItem->getInternalRate() + $entry->getInternalRate());
        } else {
            $invoiceItem->setInternalRate($invoiceItem->getInternalRate() + $entry->getRate());
        }
        $invoiceItem->setDuration($duration);

        if (null !== $entry->getFixedRate()) {
            /*
            if (null !== $invoiceItem->getFixedRate() && $invoiceItem->getFixedRate() !== $entry->getFixedRate()) {
                throw new \InvalidArgumentException('Cannot mix different fixed-rates');
            }
            */
            $invoiceItem->setFixedRate($entry->getFixedRate());
        }

        if (null !== $entry->getHourlyRate()) {
            /*
            if (null !== $invoiceItem->getHourlyRate() && $invoiceItem->getHourlyRate() !== $entry->getHourlyRate()) {
                throw new \InvalidArgumentException('Cannot mix different hourly-rates');
            }
            */
            $invoiceItem->setHourlyRate($entry->getHourlyRate());
        }

        if (null === $invoiceItem->getBegin() || $invoiceItem->getBegin()->getTimestamp() > $entry->getBegin()->getTimestamp()) {
            $invoiceItem->setBegin($entry->getBegin());
        }

        if (null === $invoiceItem->getEnd() || $invoiceItem->getEnd()->getTimestamp() < $entry->getEnd()->getTimestamp()) {
            $invoiceItem->setEnd($entry->getEnd());
        }

        if (!empty($entry->getDescription())) {
            $description = '';
            if (!empty($invoiceItem->getDescription())) {
                $description = $invoiceItem->getDescription() . PHP_EOL;
            }
            $invoiceItem->setDescription($description . $entry->getDescription());
        }

        if (null === $invoiceItem->getActivity()) {
            $invoiceItem->setActivity($entry->getActivity());
        }

        if (null === $invoiceItem->getProject()) {
            $invoiceItem->setProject($entry->getProject());
        }

        if (empty($invoiceItem->getDescription()) && null !== $entry->getActivity()) {
            $invoiceItem->setDescription($entry->getActivity()->getName());
        }
    }
}

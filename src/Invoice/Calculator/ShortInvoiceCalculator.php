<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Entity\Timesheet;
use App\Invoice\CalculatorInterface;

/**
 * A calculator that sums up all timesheet records from the model and returns only one
 * entry for a compact invoice version.
 */
class ShortInvoiceCalculator extends AbstractCalculator implements CalculatorInterface
{
    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        $timesheet = new Timesheet();

        foreach ($this->model->getEntries() as $entry) {
            $timesheet->setFixedRate($entry->getFixedRate()); // FIXME invoice
            $timesheet->setHourlyRate($entry->getHourlyRate()); // FIXME invoice
            $timesheet->setRate($timesheet->getRate() + $entry->getRate());
            $timesheet->setDuration($timesheet->getDuration() + $entry->getDuration());
            $timesheet->setBegin($entry->getBegin());
            $timesheet->setUser($entry->getUser());

            if (null === $timesheet->getActivity()) {
                $timesheet->setActivity($entry->getActivity());
            }

            if (empty($timesheet->getDescription())) {
                $timesheet->setDescription($entry->getActivity()->getName());
            }
        }

        if (null !== $this->model->getQuery()->getActivity()) {
            $timesheet->setDescription($this->model->getQuery()->getActivity()->getName());
        } elseif (null !== $this->model->getQuery()->getProject()) {
            $timesheet->setDescription($this->model->getQuery()->getProject()->getName());
        }

        return [$timesheet];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'short';
    }
}

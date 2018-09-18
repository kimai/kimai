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
        $entries = $this->model->getEntries();
        if (empty($entries)) {
            return [];
        }

        $timesheet = new Timesheet();

        foreach ($entries as $entry) {
            $this->mergeTimesheets($timesheet, $entry);
        }

        return [$timesheet];
    }

    /**
     * @param Timesheet $timesheet
     * @param Timesheet $entry
     */
    protected function mergeTimesheets(Timesheet $timesheet, Timesheet $entry)
    {
        $timesheet->setUser($entry->getUser());
        $timesheet->setFixedRate($entry->getFixedRate()); // FIXME invoice
        $timesheet->setHourlyRate($entry->getHourlyRate()); // FIXME invoice
        $timesheet->setRate($timesheet->getRate() + $entry->getRate());
        $timesheet->setDuration($timesheet->getDuration() + $entry->getDuration());

        if (null == $timesheet->getBegin() || $timesheet->getBegin()->getTimestamp() > $entry->getBegin()->getTimestamp()) {
            $timesheet->setBegin($entry->getBegin());
        }

        if (null == $timesheet->getEnd() || $timesheet->getEnd()->getTimestamp() < $entry->getEnd()->getTimestamp()) {
            $timesheet->setEnd($entry->getEnd());
        }

        if (null !== $this->model->getQuery()->getActivity()) {
            $timesheet->setActivity($this->model->getQuery()->getActivity());
            $timesheet->setDescription($this->model->getQuery()->getActivity()->getName());
        } elseif (null !== $this->model->getQuery()->getProject()) {
            $timesheet->setDescription($this->model->getQuery()->getProject()->getName());
        }

        if (null === $timesheet->getActivity()) {
            $timesheet->setActivity($entry->getActivity());
        }
        if (empty($timesheet->getDescription())) {

            $timesheet->setDescription($entry->getActivity()->getName());
        }
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'short';
    }
}

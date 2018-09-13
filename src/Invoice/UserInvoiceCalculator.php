<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Timesheet;

/**
 * A calculator that sums up the timesheet records by user.
 */
class UserInvoiceCalculator extends DefaultCalculator
{
    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        /** @var Timesheet[] $timesheets */
        $timesheets = [];

        foreach ($this->model->getEntries() as $entry) {
            if (!isset($timesheets[$entry->getUser()->getId()])) {
                $timesheets[$entry->getUser()->getId()] = new Timesheet();
            }
            $timesheet = $timesheets[$entry->getUser()->getId()];
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
            if (null === $timesheet->getActivity()) {
                $timesheet->setActivity($entry->getActivity());
            }
        }

        if (null !== $this->model->getQuery()->getActivity()) {
            $timesheet->setActivity($this->model->getQuery()->getActivity());
        }

        if (null !== $this->model->getQuery()->getActivity()) {
            $timesheet->setDescription($this->model->getQuery()->getActivity()->getName());
        } elseif (null !== $this->model->getQuery()->getProject()) {
            $timesheet->setDescription($this->model->getQuery()->getProject()->getName());
        }

        return array_values($timesheets);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'user';
    }
}

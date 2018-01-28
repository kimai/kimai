<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Timesheet;

/**
 * A calculator that sums up all timesheet records from the model and returns only one
 * entry for a compact invoice version.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ShortInvoiceCalculator extends DefaultCalculator
{

    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        $timesheet = new Timesheet();

        foreach ($this->model->getEntries() as $entry) {
            $timesheet->setRate($timesheet->getRate() + $entry->getRate());
            $timesheet->setDuration($timesheet->getDuration() + $entry->getDuration());
            if ($timesheet->getActivity() === null) {
                $timesheet->setActivity($entry->getActivity());
                $timesheet->setEnd($entry->getEnd());
            }
            $timesheet->setBegin($entry->getBegin());
        }

        return [$timesheet];
    }
}

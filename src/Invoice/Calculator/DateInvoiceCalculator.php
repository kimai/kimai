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
 * A calculator that sums up the timesheet records for each day.
 */
class DateInvoiceCalculator extends AbstractSumInvoiceCalculator implements CalculatorInterface
{
    protected function calculateSumIdentifier(Timesheet $timesheet): string
    {
        return $timesheet->getBegin()->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'date';
    }
}

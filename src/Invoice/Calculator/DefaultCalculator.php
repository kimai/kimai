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
 * Class DefaultCalculator works on all given entries using:
 * - the customers currency
 * - the invoice template vat rate
 * - the entries rate
 */
class DefaultCalculator extends AbstractCalculator implements CalculatorInterface
{
    /**
     * @return Timesheet[]
     */
    public function getEntries()
    {
        return $this->model->getEntries();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'default';
    }
}

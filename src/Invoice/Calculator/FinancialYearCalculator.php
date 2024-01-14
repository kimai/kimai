<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice\Calculator;

use App\Configuration\SystemConfiguration;

class FinancialYearCalculator
{
    public function __construct(private SystemConfiguration $systemConfiguration)
    {
    }

    private function isYearPrevious(\DateTimeInterface $dateTime): bool
    {
        if (!$financialYearStart = $this->systemConfiguration->getFinancialYearStart()){
            $financialYearStart = date('Y') . '-01-01';
        }

        $financialYearStart = \DateTime::createFromFormat('Y-m-d', $financialYearStart);

        $financialYearStart->setDate(
            $dateTime->format('Y'),
            $financialYearStart->format('m'),
            $financialYearStart->format('d')
        );

        return $dateTime->getTimestamp() <= $financialYearStart->getTimestamp();
    }

    public function getLongFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('Y') - 1;
        }

        return $dateTime->format('Y');
    }

    public function getShortFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('y') - 1;
        }

        return $dateTime->format('y');
    }

    public function getFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return ($dateTime->format('Y') - 1) . '-' . $dateTime->format('y');
        }

        return $dateTime->format('Y') . '-' . ($dateTime->format('y') + 1);
    }
}

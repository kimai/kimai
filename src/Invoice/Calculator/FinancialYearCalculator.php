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
    private \DateTimeInterface|null $financialYearStart = null;
    private \DateTimeInterface|null $financialYearEnd = null;

    public function __construct(private SystemConfiguration $systemConfiguration)
    {
        if (!$financialYearStart = $this->systemConfiguration->getFinancialYearStart()){
            return;
        }
        $this->financialYearStart = \DateTime::createFromFormat('Y-m-d', $financialYearStart);
        $this->setFinancialYearEndFromStartDate($this->financialYearStart);
    }

    private function setFinancialYearEndFromStartDate(\DateTimeInterface $financialYearStart): void
    {
        $this->financialYearEnd = $financialYearStart
            ->add(\DateInterval::createFromDateString('1 year'))
            ->sub(\DateInterval::createFromDateString('1 day'));
    }

    /**
     * @throws FinancialYearNotSetException
     */
    private function isYearPrevious(\DateTimeInterface $dateTime): bool
    {
        if (!$this->financialYearStart){
            throw new FinancialYearNotSetException('Financial year not set!');
        }

        $financialYearStart = clone $this->financialYearStart;

        $financialYearStart->setDate(
            $dateTime->format('Y'),
            $financialYearStart->format('m'),
            $financialYearStart->format('d')
        );

        return $dateTime->getTimestamp() < $financialYearStart->getTimestamp();
    }

    /**
     * @throws FinancialYearNotSetException
     */
    public function getLongFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('Y') - 1;
        }

        return $dateTime->format('Y');
    }

    /**
     * @throws FinancialYearNotSetException
     */
    public function getShortFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('y') - 1;
        }

        return $dateTime->format('y');
    }

    /**
     * @throws FinancialYearNotSetException
     */
    public function getFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return ($dateTime->format('Y') - 1) . '-' . $dateTime->format('y');
        }

        return $dateTime->format('Y') . '-' . ($dateTime->format('y') + 1);
    }

    public function getFinancialYearStart(): \DateTimeInterface|bool
    {
        return $this->financialYearStart;
    }
}

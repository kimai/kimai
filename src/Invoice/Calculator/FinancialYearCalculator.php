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
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function isYearPrevious(\DateTimeInterface $dateTime): bool
    {
        $financialYearStart = $this->getFinancialYearStart();

        $financialYearStart->setDate(
            $dateTime->format('Y'),
            $financialYearStart->format('m'),
            $financialYearStart->format('d')
        );

        return $dateTime->getTimestamp() < $financialYearStart->getTimestamp();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getLongFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('Y') - 1;
        }

        return $dateTime->format('Y');
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getShortFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return $dateTime->format('y') - 1;
        }

        return $dateTime->format('y');
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFinancialYear(\DateTimeInterface $dateTime): string
    {
        if ($this->isYearPrevious($dateTime)) {
            return ($dateTime->format('Y') - 1) . '-' . $dateTime->format('y');
        }

        return $dateTime->format('Y') . '-' . ($dateTime->format('y') + 1);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFinancialYearStart(): \DateTimeInterface
    {
        if (($financialYearStart = $this->systemConfiguration->getFinancialYearStart()) === null){
            throw new \InvalidArgumentException('Financial year not set');
        }

        return \DateTimeImmutable::createFromFormat('Y-m-d', $financialYearStart);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFinancialYearEnd(): \DateTimeInterface
    {
        return $this->getFinancialYearStart()
            ->add(\DateInterval::createFromDateString('1 year'))
            ->sub(\DateInterval::createFromDateString('1 day'));
    }
}

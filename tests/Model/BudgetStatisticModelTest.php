<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\Customer;
use App\Entity\EntityWithBudget;
use App\Model\BudgetStatisticModel;
use App\Model\Statistic\BudgetStatistic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\BudgetStatisticModel
 */
class BudgetStatisticModelTest extends TestCase
{
    protected function getSut(EntityWithBudget $entity): BudgetStatisticModel
    {
        return new BudgetStatisticModel($entity);
    }

    protected function getEntity(): EntityWithBudget
    {
        return new Customer('foo');
    }

    public function testDefaults(): void
    {
        $this->assertDefaults();
    }

    public function testSetter(): void
    {
        $this->assertSetter();
    }

    public function testCalculation(): void
    {
        $this->assertCalculation();
    }

    protected function assertCalculation(): void
    {
        $entity = $this->getEntity();
        $entity->setBudget(100.0);
        $entity->setTimeBudget(100);
        $entity->setBudgetType('month');
        $sut = $this->getSut($entity);

        $statistic = new BudgetStatistic();
        $statistic->setInternalRate(147.95);
        $statistic->setRate(47.00);
        $statistic->setRateBillable(13.00);
        $statistic->setDuration(53);
        $statistic->setDurationBillable(23);
        $sut->setStatistic($statistic);

        $statisticTotal = new BudgetStatistic();
        $statisticTotal->setInternalRate(247.95);
        $statisticTotal->setRate(247.00);
        $statisticTotal->setRateBillable(213.00);
        $statisticTotal->setDuration(253);
        $statisticTotal->setDurationBillable(223);
        $sut->setStatisticTotal($statisticTotal);

        self::assertSame($entity, $sut->getEntity());
        self::assertSame(23, $sut->getDurationBillable());
        self::assertSame(23, $sut->getDurationBillableRelative());
        self::assertSame(223, $sut->getDurationBillableTotal());
        self::assertSame(53, $sut->getDuration());
        self::assertSame(13.00, $sut->getRateBillable());
        self::assertSame(13.00, $sut->getRateBillableRelative());
        self::assertSame(213.00, $sut->getRateBillableTotal());
        self::assertSame(47.00, $sut->getRate());
        self::assertSame(147.95, $sut->getInternalRate());

        self::assertTrue($sut->isMonthlyBudget());

        self::assertTrue($sut->hasTimeBudget());
        self::assertSame(100, $sut->getTimeBudget());
        self::assertSame(77, $sut->getTimeBudgetOpen());
        self::assertSame(23, $sut->getTimeBudgetSpent());

        self::assertTrue($sut->hasBudget());
        self::assertSame(100.00, $sut->getBudget());
        self::assertSame(87.00, $sut->getBudgetOpen());
        self::assertSame(13.00, $sut->getBudgetSpent());

        self::assertSame($statistic, $sut->getStatistic());
        self::assertSame($statisticTotal, $sut->getStatisticTotal());

        $entity->setBudgetType(null);

        self::assertSame(223, $sut->getDurationBillable());
        self::assertSame(253, $sut->getDuration());
        self::assertSame(213.00, $sut->getRateBillable());
        self::assertSame(247.00, $sut->getRate());
        self::assertSame(247.95, $sut->getInternalRate());

        self::assertSame(100, $sut->getTimeBudget());
        self::assertSame(0, $sut->getTimeBudgetOpen());
        self::assertSame(223, $sut->getTimeBudgetSpent());

        self::assertSame(100.00, $sut->getBudget());
        self::assertSame(0.00, $sut->getBudgetOpen());
        self::assertSame(213.00, $sut->getBudgetSpent());
    }

    protected function assertSetter(): void
    {
        $entity = $this->getEntity();
        $entity->setBudget(10.0);
        $entity->setTimeBudget(10);
        $entity->setBudgetType('month');
        $sut = $this->getSut($entity);

        self::assertSame($entity, $sut->getEntity());
        self::assertSame(0, $sut->getDurationBillable());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0.00, $sut->getRateBillable());
        self::assertSame(0.00, $sut->getRate());
        self::assertSame(0.00, $sut->getInternalRate());

        self::assertTrue($sut->isMonthlyBudget());

        self::assertTrue($sut->hasTimeBudget());
        self::assertSame(10, $sut->getTimeBudget());
        self::assertSame(10, $sut->getTimeBudgetOpen());
        self::assertSame(0, $sut->getTimeBudgetSpent());

        self::assertTrue($sut->hasBudget());
        self::assertSame(10.00, $sut->getBudget());
        self::assertSame(10.00, $sut->getBudgetOpen());
        self::assertSame(0.00, $sut->getBudgetSpent());

        self::assertNull($sut->getStatisticTotal());
        self::assertNull($sut->getStatistic());
    }

    protected function assertDefaults(): void
    {
        $entity = $this->getEntity();
        $sut = $this->getSut($entity);

        self::assertSame($entity, $sut->getEntity());
        self::assertSame(0, $sut->getDurationBillable());
        self::assertSame(0, $sut->getDuration());
        self::assertSame(0.00, $sut->getRateBillable());
        self::assertSame(0.00, $sut->getRate());
        self::assertSame(0.00, $sut->getInternalRate());

        self::assertFalse($sut->isMonthlyBudget());

        self::assertFalse($sut->hasTimeBudget());
        self::assertSame(0, $sut->getTimeBudget());
        self::assertSame(0, $sut->getTimeBudgetOpen());
        self::assertSame(0, $sut->getTimeBudgetSpent());

        self::assertFalse($sut->hasBudget());
        self::assertSame(0.00, $sut->getBudget());
        self::assertSame(0.00, $sut->getBudgetOpen());
        self::assertSame(0.00, $sut->getBudgetSpent());

        self::assertNull($sut->getStatisticTotal());
        self::assertNull($sut->getStatistic());
    }
}

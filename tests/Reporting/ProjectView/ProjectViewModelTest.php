<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectView;

use App\Entity\Project;
use App\Model\ProjectBudgetStatisticModel;
use App\Model\Statistic\BudgetStatistic;
use App\Reporting\ProjectView\ProjectViewModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectView\ProjectViewModel
 */
class ProjectViewModelTest extends TestCase
{
    public function testDefaults(): void
    {
        $project = new Project();
        $model = new ProjectBudgetStatisticModel($project);
        $total = new BudgetStatistic();
        $model->setStatisticTotal($total);
        $sut = new ProjectViewModel($model);

        self::assertSame($project, $sut->getProject());
        self::assertSame(0, $sut->getDurationDay());
        self::assertSame(0, $sut->getDurationMonth());
        self::assertSame(0, $sut->getDurationTotal());
        self::assertSame(0, $sut->getDurationWeek());
        self::assertSame(0, $sut->getNotExportedDuration());
        self::assertSame(0.0, $sut->getNotExportedRate());
        self::assertSame(0.0, $sut->getRateTotal());
        self::assertNull($sut->getLastRecord());
        self::assertNull($sut->getLastRecord());
        self::assertSame(0.0, $sut->getBillableRate());
        self::assertSame(0, $sut->getBillableDuration());
    }

    public function testSetterGetter(): void
    {
        $project = new Project();

        $model = new ProjectBudgetStatisticModel($project);
        $total = new BudgetStatistic();
        $total->setCounter(123);
        $total->setDuration(3456789);
        $total->setDurationBillable(3456789);
        $total->setDurationBillableExported(3400000);
        $total->setRate(789.0);
        $total->setRateBillable(16789.0);
        $total->setRateBillableExported(10000.0);
        $model->setStatisticTotal($total);

        $sut = new ProjectViewModel($model);

        $date = new \DateTime();

        $sut->setDurationDay(123456789);
        $sut->setDurationMonth(23456789);
        $sut->setDurationWeek(456789);
        $sut->setLastRecord($date);

        self::assertSame(123456789, $sut->getDurationDay());
        self::assertSame(23456789, $sut->getDurationMonth());
        self::assertSame(3456789, $sut->getDurationTotal());
        self::assertSame(456789, $sut->getDurationWeek());
        self::assertSame(56789, $sut->getNotExportedDuration());
        self::assertSame(6789.0, $sut->getNotExportedRate());
        self::assertSame(789.0, $sut->getRateTotal());
        self::assertSame($date, $sut->getLastRecord());

        $date = new \DateTime();
        $sut->setLastRecord($date);

        self::assertSame($date, $sut->getLastRecord());
        self::assertSame(16789.0, $sut->getBillableRate());
        self::assertSame(3456789, $sut->getBillableDuration());
    }
}

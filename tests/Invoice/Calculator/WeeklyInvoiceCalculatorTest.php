<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Calculator;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Invoice\Calculator\WeeklyInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use DateTime;

/**
 * @covers \App\Invoice\Calculator\WeeklyInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractSumInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class WeeklyInvoiceCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new WeeklyInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $user = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);

        $project1 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project1->method('getId')->willReturn(1);

        $project2 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project2->method('getId')->willReturn(2);

        $project3 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project3->method('getId')->willReturn(3);

        $timezone = new \DateTimeZone('Europe/Berlin');
        $end = new \DateTime('now', $timezone);

        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime('2018-11-26 12:00:00', $timezone));
        $timesheet->setEnd(clone $end);
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user);
        $timesheet->setActivity((new Activity())->setName('sdsd'));
        $timesheet->setProject($project1);

        $timesheet2 = new Timesheet();
        $timesheet2->setBegin(new DateTime('2018-11-26 12:00:00', $timezone));
        $timesheet2->setEnd(clone $end);
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user);
        $timesheet2->setActivity((new Activity())->setName('bar'));
        $timesheet2->setProject($project2);

        $timesheet3 = new Timesheet();
        $timesheet3->setBegin(new DateTime('2018-11-25 12:00:00', $timezone));
        $timesheet3->setEnd(clone $end);
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user);
        $timesheet3->setActivity((new Activity())->setName('foo'));
        $timesheet3->setProject($project1);

        $timesheet4 = new Timesheet();
        $timesheet4->setBegin(new DateTime('2018-11-25 12:00:00', $timezone));
        $timesheet4->setEnd(clone $end);
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user);
        $timesheet4->setActivity((new Activity())->setName('blub'));
        $timesheet4->setProject($project2);

        $timesheet5 = new Timesheet();
        $timesheet5->setBegin(new DateTime('2018-11-25 12:00:00', $timezone));
        $timesheet5->setEnd(clone $end);
        $timesheet5->setDuration(400);
        $timesheet5->setRate(84);
        $timesheet5->setUser(new User());
        $timesheet5->setActivity(new Activity());
        $timesheet5->setProject($project3);

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new InvoiceQuery();
        $query->setProjects([$project1]);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('weekly', $sut->getId());
        self::assertEquals(3000.13, $sut->getTotal());
        self::assertEquals(19, $sut->getVat());
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(2521.12, $sut->getSubtotal());
        self::assertEquals(6600, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(2, $entries);
        self::assertEquals(378.02, $entries[1]->getRate());
        self::assertEquals(2143.1, $entries[0]->getRate());
    }

    public function testDescriptionByTimesheet(): void
    {
        $this->assertDescription($this->getCalculator(), false, false);
    }
}

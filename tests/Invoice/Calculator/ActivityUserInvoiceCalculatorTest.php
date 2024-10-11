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
use App\Invoice\Calculator\ActivityUserInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;

/**
 * @covers \App\Invoice\Calculator\ActivityUserInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractSumInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class ActivityUserInvoiceCalculatorTest extends AbstractCalculatorTest
{
    protected function getCalculator(): CalculatorInterface
    {
        return new ActivityUserInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $date = new \DateTime();
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $user1 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);

        $user2 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);

        $activity1 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $activity1->method('getId')->willReturn(1);

        $activity2 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $activity2->method('getId')->willReturn(2);

        $activity3 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $activity3->method('getId')->willReturn(3);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity1)
            ->setProject((new Project())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setBegin(new \DateTime('2018-11-18'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user1)
            ->setActivity($activity2)
            ->setProject((new Project())->setName('bar'));

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setBegin(clone $date)
            ->setEnd(new \DateTime())
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity1)
            ->setProject((new Project())->setName('bar'));

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setBegin(new \DateTime('2018-11-29'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user1)
            ->setActivity($activity2)
            ->setProject((new Project())->setName('bar'));

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setBegin(new \DateTime('2018-11-18'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84)
            ->setUser($user2)
            ->setActivity($activity3)
            ->setProject((new Project())->setName('bar'));

        $timesheet5a = new Timesheet();
        $timesheet5a
            ->setBegin(new \DateTime('2018-11-08'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84)
            ->setUser($user1)
            ->setActivity($activity3)
            ->setProject((new Project())->setName('bar'));

        $timesheet6 = new Timesheet();
        $timesheet6
            ->setBegin(clone $date)
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser($user1)
            ->setProject((new Project())->setName('bar'));

        $timesheet7 = new Timesheet();
        $timesheet7
            ->setBegin(clone $date)
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser($user2)
            ->setActivity(new Activity())
            ->setProject((new Project())->setName('bar'));

        $timesheet8 = new Timesheet();
        $timesheet8
            ->setBegin(clone $date)
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser($user2)
            ->setProject((new Project())->setName('bar'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet5a, $timesheet6, $timesheet7, $timesheet8];

        $query = new InvoiceQuery();
        $query->addActivity($activity1);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        $this->assertEquals('activity_user', $sut->getId());
        $this->assertEquals(3100.09, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $model->getCurrency());
        $this->assertEquals(2605.12, $sut->getSubtotal());
        $this->assertEquals(7000, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(6, $entries);
        $this->assertEquals('2018-11-08', $entries[0]->getBegin()?->format('Y-m-d'));
        $this->assertEquals('2018-11-18', $entries[1]->getBegin()?->format('Y-m-d'));
        $this->assertEquals('2018-11-18', $entries[2]->getBegin()?->format('Y-m-d'));
        $this->assertEquals($date->format('Y-m-d'), $entries[3]->getBegin()?->format('Y-m-d'));
        $this->assertEquals($date->format('Y-m-d'), $entries[4]->getBegin()?->format('Y-m-d'));
        $this->assertEquals($date->format('Y-m-d'), $entries[5]->getBegin()?->format('Y-m-d'));

        $this->assertEquals(404.38, $entries[5]->getRate());
        $this->assertEquals(2032.74, $entries[1]->getRate());
        $this->assertEquals(84.0, $entries[2]->getRate());
        $this->assertEquals(84.0, $entries[0]->getRate());
        $this->assertEquals(0, $entries[4]->getRate());
        $this->assertEquals(0, $entries[3]->getRate());
    }

    public function testDescriptionByActivity(): void
    {
        $this->assertDescription($this->getCalculator(), false, true);
    }
}

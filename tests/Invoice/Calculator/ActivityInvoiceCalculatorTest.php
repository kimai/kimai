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
use App\Invoice\Calculator\ActivityInvoiceCalculator;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;

/**
 * @covers \App\Invoice\Calculator\ActivityInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractSumInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class ActivityInvoiceCalculatorTest extends AbstractCalculatorTest
{
    public function testEmptyModel()
    {
        $this->assertEmptyModel(new ActivityInvoiceCalculator());
    }

    public function testWithMultipleEntries()
    {
        $customer = new Customer();
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $user = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);

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
            ->setUser($user)
            ->setActivity($activity1)
            ->setProject((new Project())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user)
            ->setActivity($activity2)
            ->setProject((new Project())->setName('bar'));

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user)
            ->setActivity($activity1)
            ->setProject((new Project())->setName('bar'));

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user)
            ->setActivity($activity2)
            ->setProject((new Project())->setName('bar'));

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84)
            ->setUser(new User())
            ->setActivity($activity3)
            ->setProject((new Project())->setName('bar'));

        $timesheet6 = new Timesheet();
        $timesheet6
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser(new User())
            ->setProject((new Project())->setName('bar'));

        $timesheet7 = new Timesheet();
        $timesheet7
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser(new User())
            ->setActivity(new Activity())
            ->setProject((new Project())->setName('bar'));

        $timesheet8 = new Timesheet();
        $timesheet8
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(0)
            ->setRate(0)
            ->setUser(new User())
            ->setProject((new Project())->setName('bar'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet6, $timesheet7, $timesheet8];

        $query = new InvoiceQuery();
        $query->setActivity($activity1);

        $model = new InvoiceModel(new DebugFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery($query);

        $sut = new ActivityInvoiceCalculator();
        $sut->setModel($model);

        $this->assertEquals('activity', $sut->getId());
        $this->assertEquals(3000.13, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $model->getCurrency());
        $this->assertEquals(2521.12, $sut->getSubtotal());
        $this->assertEquals(6600, $sut->getTimeWorked());
        $this->assertEquals(5, \count($sut->getEntries()));

        $entries = $sut->getEntries();
        $this->assertEquals(404.38, $entries[0]->getRate());
        $this->assertEquals(2032.74, $entries[1]->getRate());
        $this->assertEquals(84, $entries[2]->getRate());
    }

    public function testDescriptionByActivity()
    {
        $this->assertDescription(new ActivityInvoiceCalculator(), false, true);
    }
}

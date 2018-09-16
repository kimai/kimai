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
use App\Entity\Timesheet;
use App\Entity\User;
use App\Invoice\Calculator\ShortInvoiceCalculator;
use App\Invoice\Calculator\UserInvoiceCalculator;
use App\Model\InvoiceModel;
use App\Repository\Query\InvoiceQuery;

/**
 * @covers \App\Invoice\Calculator\UserInvoiceCalculator
 */
class UserInvoiceCalculatorTest extends AbstractCalculatorTest
{
    public function testEmptyModel()
    {
        $this->assertEmptyModel(new UserInvoiceCalculator());
    }

    public function testWithMultipleEntries()
    {
        $customer = new Customer();
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $activity = new Activity();
        $activity->setName('activity description');

        $user1 = $this->getMockBuilder(User::class)->setMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);

        $user2 = $this->getMockBuilder(User::class)->setMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity)
        ;

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user2)
            ->setActivity($activity)
        ;

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setDuration(400)
            ->setRate(84)
            ->setUser(new User())
            ->setActivity($activity)
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new InvoiceQuery();
        $query->setActivity($activity);

        $model = new InvoiceModel();
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->setEntries($entries);
        $model->setQuery($query);

        $sut = new UserInvoiceCalculator();
        $sut->setModel($model);

        $this->assertEquals(3000.13, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $sut->getCurrency());
        $this->assertEquals(2521.12, $sut->getSubtotal());
        $this->assertEquals(6600, $sut->getTimeWorked());
        $this->assertEquals(3, count($sut->getEntries()));

        $entries = $sut->getEntries();
        $this->assertEquals(404.38, $entries[0]->getRate());
        $this->assertEquals(2032.74, $entries[1]->getRate());
        $this->assertEquals(84, $entries[2]->getRate());
    }


    public function testDescriptionByTimesheet()
    {
        $this->assertDescription(new UserInvoiceCalculator(), false, false);
    }

    public function testDescriptionByActivity()
    {
        $this->assertDescription(new UserInvoiceCalculator(), false, true);
    }

    public function testDescriptionByProject()
    {
        $this->assertDescription(new UserInvoiceCalculator(), true, false);
    }
}

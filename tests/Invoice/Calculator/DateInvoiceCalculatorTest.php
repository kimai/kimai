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
use App\Invoice\Calculator\DateInvoiceCalculator;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use DateTime;

/**
 * @covers \App\Invoice\Calculator\DateInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractSumInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class DateInvoiceCalculatorTest extends AbstractCalculatorTest
{
    public function testEmptyModel()
    {
        $this->assertEmptyModel(new DateInvoiceCalculator());
    }

    public function testWithMultipleEntries()
    {
        $customer = new Customer();
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

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new DateTime('2018-11-29'))
            ->setEnd(new DateTime())
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user)
            ->setActivity((new Activity())->setName('sdsd'))
            ->setProject($project1);

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setBegin(new DateTime('2018-11-29'))
            ->setEnd(new DateTime())
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user)
            ->setActivity((new Activity())->setName('bar'))
            ->setProject($project2);

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setBegin(new DateTime('2018-11-28'))
            ->setEnd(new DateTime())
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user)
            ->setActivity((new Activity())->setName('foo'))
            ->setProject($project1);

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setBegin(new DateTime())
            ->setEnd(new DateTime('2018-11-28'))
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user)
            ->setActivity((new Activity())->setName('blub'))
            ->setProject($project2);

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setBegin(new DateTime('2018-11-28'))
            ->setEnd(new DateTime())
            ->setDuration(400)
            ->setRate(84)
            ->setUser(new User())
            ->setActivity(new Activity())
            ->setProject($project3);

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new InvoiceQuery();
        $query->setProjects([$project1]);

        $model = new InvoiceModel(new DebugFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery($query);

        $sut = new DateInvoiceCalculator();
        $sut->setModel($model);

        $this->assertEquals('date', $sut->getId());
        $this->assertEquals(3000.13, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $model->getCurrency());
        $this->assertEquals(2521.12, $sut->getSubtotal());
        $this->assertEquals(6600, $sut->getTimeWorked());
        $this->assertEquals(3, \count($sut->getEntries()));

        $entries = $sut->getEntries();
        self::assertCount(3, $entries);
        $this->assertEquals(378.02, $entries[0]->getRate());
        $this->assertEquals(195.11, $entries[1]->getRate());
        $this->assertEquals(1947.99, $entries[2]->getRate());
        self::assertEquals(2521.12, $entries[0]->getRate() + $entries[1]->getRate() + $entries[2]->getRate());
    }

    public function testDescriptionByTimesheet()
    {
        $this->assertDescription(new DateInvoiceCalculator(), false, false);
    }
}

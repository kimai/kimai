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
use App\Invoice\Calculator\PriceInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use DateTime;

/**
 * @covers \App\Invoice\Calculator\PriceInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractSumInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class PriceInvoiceCalculatorTest extends AbstractCalculatorTest
{
    protected function getCalculator(): CalculatorInterface
    {
        return new PriceInvoiceCalculator();
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

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new DateTime('2018-11-29'))
            ->setEnd(new DateTime())
            ->setDuration(3600)
            ->setHourlyRate(293.27)
            ->setRate(293.27)
            ->setUser($user)
            ->setActivity((new Activity())->setName('sdsd'))
            ->setProject($project1);

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setBegin(new DateTime('2018-11-29'))
            ->setEnd(new DateTime())
            ->setDuration(400)
            ->setHourlyRate(293.27)
            ->setRate(84.75)
            ->setUser($user)
            ->setActivity((new Activity())->setName('bar'))
            ->setProject($project2);

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setBegin(new DateTime('2018-11-28'))
            ->setEnd(new DateTime())
            ->setDuration(1800)
            ->setFixedRate(111.11)
            ->setRate(111.11)
            ->setUser($user)
            ->setActivity((new Activity())->setName('foo'))
            ->setProject($project1);

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setBegin(new DateTime())
            ->setEnd(new DateTime('2018-11-28'))
            ->setDuration(400)
            ->setHourlyRate(0)
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

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        $this->assertEquals('price', $sut->getId());
        $this->assertEquals(3000.13, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $model->getCurrency());
        $this->assertEquals(2521.12, $sut->getSubtotal());
        $this->assertEquals(6600, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(4, $entries);
        $this->assertEquals(378.02, $entries[0]->getRate());
        $this->assertEquals(111.11, $entries[1]->getRate());
        $this->assertEquals(1947.99, $entries[2]->getRate());
        $this->assertEquals(84, $entries[3]->getRate());
    }

    public function testDescriptionByTimesheet(): void
    {
        $this->assertDescription($this->getCalculator(), false, false);
    }
}

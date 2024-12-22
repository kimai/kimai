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
use App\Invoice\Calculator\UserInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;

/**
 * @covers \App\Invoice\Calculator\UserInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class UserInvoiceCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new UserInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $activity = new Activity();
        $activity->setName('activity description');

        $user1 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);

        $user2 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);

        $user3 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user3->method('getId')->willReturn(3);

        $timesheet = new Timesheet();
        $timesheet
            ->setBegin(new \DateTime('2018-11-29'))
            ->setEnd(new \DateTime())
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject((new Project())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setBegin(new \DateTime('2018-11-28'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84.75)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject((new Project())->setName('bar'));

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setBegin(new \DateTime('2018-11-08'))
            ->setEnd(new \DateTime())
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser($user1)
            ->setActivity($activity)
            ->setProject((new Project())->setName('bar'));

        $timesheet4 = new Timesheet();
        $timesheet4
            ->setBegin(new \DateTime('2018-11-28'))
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(1947.99)
            ->setUser($user2)
            ->setActivity($activity)
            ->setProject((new Project())->setName('bar'));

        $timesheet5 = new Timesheet();
        $timesheet5
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
            ->setDuration(400)
            ->setRate(84)
            ->setUser($user3)
            ->setActivity($activity)
            ->setProject((new Project())->setName('bar'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5];

        $query = new InvoiceQuery();
        $query->addActivity($activity);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('user', $sut->getId());
        self::assertEquals(3000.13, $sut->getTotal());
        self::assertEquals(19, $sut->getVat());
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(2521.12, $sut->getSubtotal());
        self::assertEquals(6600, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(3, $entries);
        self::assertEquals(404.38, $entries[0]->getRate());
        self::assertEquals(2032.74, $entries[1]->getRate());
        self::assertEquals(84, $entries[2]->getRate());
    }

    public function testDescriptionByTimesheet(): void
    {
        $this->assertDescription($this->getCalculator(), false, false);
    }
}

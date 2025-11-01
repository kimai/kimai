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
use App\Invoice\Calculator\AbstractCalculator;
use App\Invoice\Calculator\AbstractMergedCalculator;
use App\Invoice\Calculator\AbstractSumInvoiceCalculator;
use App\Invoice\Calculator\ActivityUserInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityUserInvoiceCalculator::class)]
#[CoversClass(AbstractSumInvoiceCalculator::class)]
#[CoversClass(AbstractMergedCalculator::class)]
#[CoversClass(AbstractCalculator::class)]
class ActivityUserInvoiceCalculatorTest extends AbstractCalculatorTestCase
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
        $timesheet->setBegin(new \DateTime());
        $timesheet->setEnd(new \DateTime());
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user1);
        $timesheet->setActivity($activity1);
        $timesheet->setProject((new Project())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2->setBegin(new \DateTime('2018-11-18'));
        $timesheet2->setEnd(new \DateTime());
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user1);
        $timesheet2->setActivity($activity2);
        $timesheet2->setProject((new Project())->setName('bar'));

        $timesheet3 = new Timesheet();
        $timesheet3->setBegin(clone $date);
        $timesheet3->setEnd(new \DateTime());
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user1);
        $timesheet3->setActivity($activity1);
        $timesheet3->setProject((new Project())->setName('bar'));

        $timesheet4 = new Timesheet();
        $timesheet4->setBegin(new \DateTime('2018-11-29'));
        $timesheet4->setEnd(new \DateTime());
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user1);
        $timesheet4->setActivity($activity2);
        $timesheet4->setProject((new Project())->setName('bar'));

        $timesheet5 = new Timesheet();
        $timesheet5->setBegin(new \DateTime('2018-11-18'));
        $timesheet5->setEnd(new \DateTime());
        $timesheet5->setDuration(400);
        $timesheet5->setRate(84);
        $timesheet5->setUser($user2);
        $timesheet5->setActivity($activity3);
        $timesheet5->setProject((new Project())->setName('bar'));

        $timesheet5a = new Timesheet();
        $timesheet5a->setBegin(new \DateTime('2018-11-08'));
        $timesheet5a->setEnd(new \DateTime());
        $timesheet5a->setDuration(400);
        $timesheet5a->setRate(84);
        $timesheet5a->setUser($user1);
        $timesheet5a->setActivity($activity3);
        $timesheet5a->setProject((new Project())->setName('bar'));

        $timesheet6 = new Timesheet();
        $timesheet6->setBegin(clone $date);
        $timesheet6->setEnd(new \DateTime());
        $timesheet6->setDuration(0);
        $timesheet6->setRate(0);
        $timesheet6->setUser($user1);
        $timesheet6->setProject((new Project())->setName('bar'));

        $timesheet7 = new Timesheet();
        $timesheet7->setBegin(clone $date);
        $timesheet7->setEnd(new \DateTime());
        $timesheet7->setDuration(0);
        $timesheet7->setRate(0);
        $timesheet7->setUser($user2);
        $timesheet7->setActivity(new Activity());
        $timesheet7->setProject((new Project())->setName('bar'));

        $timesheet8 = new Timesheet();
        $timesheet8->setBegin(clone $date);
        $timesheet8->setEnd(new \DateTime());
        $timesheet8->setDuration(0);
        $timesheet8->setRate(0);
        $timesheet8->setUser($user2);
        $timesheet8->setProject((new Project())->setName('bar'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet5a, $timesheet6, $timesheet7, $timesheet8];

        $query = new InvoiceQuery();
        $query->addActivity($activity1);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('activity_user', $sut->getId());
        self::assertEquals(3100.09, $sut->getTotal());
        $this->assertTax($sut, 19);
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(2605.12, $sut->getSubtotal());
        self::assertEquals(7000, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(6, $entries);
        self::assertEquals('2018-11-08', $entries[0]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-18', $entries[1]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-18', $entries[2]->getBegin()?->format('Y-m-d'));
        self::assertEquals($date->format('Y-m-d'), $entries[3]->getBegin()?->format('Y-m-d'));
        self::assertEquals($date->format('Y-m-d'), $entries[4]->getBegin()?->format('Y-m-d'));
        self::assertEquals($date->format('Y-m-d'), $entries[5]->getBegin()?->format('Y-m-d'));

        self::assertEquals(404.38, $entries[5]->getRate());
        self::assertEquals(2032.74, $entries[1]->getRate());
        self::assertEquals(84.0, $entries[2]->getRate());
        self::assertEquals(84.0, $entries[0]->getRate());
        self::assertEquals(0, $entries[4]->getRate());
        self::assertEquals(0, $entries[3]->getRate());
    }

    public function testDescriptionByActivity(): void
    {
        $this->assertDescription($this->getCalculator(), false, true);
    }
}

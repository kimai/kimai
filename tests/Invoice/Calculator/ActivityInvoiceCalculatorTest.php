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
use App\Invoice\Calculator\ActivityInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityInvoiceCalculator::class)]
#[CoversClass(AbstractSumInvoiceCalculator::class)]
#[CoversClass(AbstractMergedCalculator::class)]
#[CoversClass(AbstractCalculator::class)]
class ActivityInvoiceCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new ActivityInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $date = new \DateTime();
        $customer = new Customer('foo');
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
        $timesheet->setBegin(new \DateTime('2018-11-29'));
        $timesheet->setEnd(new \DateTime());
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user);
        $timesheet->setActivity($activity1);
        $timesheet->setProject((new Project())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2->setBegin(clone $date);
        $timesheet2->setEnd(new \DateTime());
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user);
        $timesheet2->setActivity($activity2);
        $timesheet2->setProject((new Project())->setName('bar'));

        $timesheet3 = new Timesheet();
        $timesheet3->setBegin(new \DateTime('2018-11-28'));
        $timesheet3->setEnd(new \DateTime());
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user);
        $timesheet3->setActivity($activity1);
        $timesheet3->setProject((new Project())->setName('bar'));

        $timesheet4 = new Timesheet();
        $timesheet4->setBegin(new \DateTime('2018-11-28'));
        $timesheet4->setEnd(new \DateTime());
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user);
        $timesheet4->setActivity($activity2);
        $timesheet4->setProject((new Project())->setName('bar'));

        $timesheet5 = new Timesheet();
        $timesheet5->setBegin(new \DateTime('2018-11-29'));
        $timesheet5->setEnd(new \DateTime());
        $timesheet5->setDuration(400);
        $timesheet5->setRate(84);
        $timesheet5->setUser(new User());
        $timesheet5->setActivity($activity3);
        $timesheet5->setProject((new Project())->setName('bar'));

        $timesheet6 = new Timesheet();
        $timesheet6->setBegin(clone $date);
        $timesheet6->setEnd(new \DateTime());
        $timesheet6->setDuration(0);
        $timesheet6->setRate(0);
        $timesheet6->setUser(new User());
        $timesheet6->setProject((new Project())->setName('bar'));

        $timesheet7 = new Timesheet();
        $timesheet7->setBegin(clone $date);
        $timesheet7->setEnd(new \DateTime('2018-11-18'));
        $timesheet7->setDuration(0);
        $timesheet7->setRate(0);
        $timesheet7->setUser(new User());
        $timesheet7->setActivity(new Activity());
        $timesheet7->setProject((new Project())->setName('bar'));

        $timesheet8 = new Timesheet();
        $timesheet8->setBegin(clone $date);
        $timesheet8->setEnd(new \DateTime());
        $timesheet8->setDuration(0);
        $timesheet8->setRate(0);
        $timesheet8->setUser(new User());
        $timesheet8->setProject((new Project())->setName('bar'));

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet6, $timesheet7, $timesheet8];

        $query = new InvoiceQuery();
        $query->addActivity($activity1);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('activity', $sut->getId());
        self::assertEquals(3000.13, $sut->getTotal());
        $this->assertTax($sut, 19);
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(2521.12, $sut->getSubtotal());
        self::assertEquals(6600, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(4, $entries);
        self::assertEquals('2018-11-28', $entries[0]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-28', $entries[1]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-29', $entries[2]->getBegin()?->format('Y-m-d'));
        self::assertEquals($date->format('Y-m-d'), $entries[3]->getBegin()?->format('Y-m-d'));

        self::assertEquals(404.38, $entries[0]->getRate());
        self::assertEquals(2032.74, $entries[1]->getRate());
        self::assertEquals(84, $entries[2]->getRate());
    }

    public function testDescriptionByActivity(): void
    {
        $this->assertDescription($this->getCalculator(), false, true);
    }
}

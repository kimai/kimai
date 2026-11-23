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
use App\Invoice\Calculator\ProjectActivityInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectActivityInvoiceCalculator::class)]
#[CoversClass(AbstractSumInvoiceCalculator::class)]
#[CoversClass(AbstractMergedCalculator::class)]
#[CoversClass(AbstractCalculator::class)]
class ProjectActivityInvoiceCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new ProjectActivityInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $user1 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user1->method('getId')->willReturn(1);

        $user2 = $this->getMockBuilder(User::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $user2->method('getId')->willReturn(2);

        $project1 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project1->method('getId')->willReturn(1);

        $project2 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project2->method('getId')->willReturn(2);

        $project3 = $this->getMockBuilder(Project::class)->onlyMethods(['getId'])->disableOriginalConstructor()->getMock();
        $project3->method('getId')->willReturn(3);

        $activity1 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId', 'getName'])->disableOriginalConstructor()->getMock();
        $activity1->method('getId')->willReturn(1);
        $activity1->method('getName')->willReturn('sdsd');

        $activity2 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId', 'getName'])->disableOriginalConstructor()->getMock();
        $activity2->method('getId')->willReturn(2);
        $activity2->method('getName')->willReturn('bar');

        $activity3 = $this->getMockBuilder(Activity::class)->onlyMethods(['getId', 'getName'])->disableOriginalConstructor()->getMock();
        $activity3->method('getId')->willReturn(3);
        $activity3->method('getName')->willReturn('foo');

        $timesheet = new Timesheet();
        $timesheet->setBegin(new DateTime('2018-11-29'));
        $timesheet->setEnd(new DateTime());
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setUser($user1);
        $timesheet->setActivity($activity1);
        $timesheet->setProject($project1);

        $timesheet2 = new Timesheet();
        $timesheet2->setBegin(new DateTime('2018-11-28'));
        $timesheet2->setEnd(new DateTime());
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84.75);
        $timesheet2->setUser($user1);
        $timesheet2->setActivity($activity2);
        $timesheet2->setProject($project2);

        $timesheet3 = new Timesheet();
        $timesheet3->setBegin(new DateTime('2018-11-29'));
        $timesheet3->setEnd(new DateTime());
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setUser($user1);
        $timesheet3->setActivity($activity1);
        $timesheet3->setProject($project1);

        $timesheet4 = new Timesheet();
        $timesheet4->setBegin(new DateTime('2018-11-08'));
        $timesheet4->setEnd(new DateTime());
        $timesheet4->setDuration(400);
        $timesheet4->setRate(1947.99);
        $timesheet4->setUser($user1);
        $timesheet4->setActivity($activity3);
        $timesheet4->setProject($project3);

        $timesheet5 = new Timesheet();
        $timesheet5->setBegin(new DateTime('2018-11-28'));
        $timesheet5->setEnd(new DateTime());
        $timesheet5->setDuration(400);
        $timesheet5->setRate(84);
        $timesheet5->setUser($user1);
        $timesheet5->setActivity($activity2);
        $timesheet5->setProject($project3);

        $timesheet6 = new Timesheet();
        $timesheet6->setBegin(new DateTime('2018-11-27'));
        $timesheet6->setEnd(new DateTime());
        $timesheet6->setDuration(1000);
        $timesheet6->setRate(100);
        $timesheet6->setUser($user2);
        $timesheet6->setActivity($activity3);
        $timesheet6->setProject($project2);

        $entries = [$timesheet, $timesheet2, $timesheet3, $timesheet4, $timesheet5, $timesheet6];

        $query = new InvoiceQuery();
        $query->setProjects([$project1]);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('project_activity', $sut->getId());
        self::assertEquals(3119.13, $sut->getTotal());
        $this->assertTax($sut, 19);
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(2621.12, $sut->getSubtotal());
        self::assertEquals(7600, $sut->getTimeWorked());

        $entries = $sut->getEntries();
        self::assertCount(5, $entries);

        self::assertEquals('2018-11-08', $entries[0]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-27', $entries[1]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-28', $entries[2]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-28', $entries[3]->getBegin()?->format('Y-m-d'));
        self::assertEquals('2018-11-29', $entries[4]->getBegin()?->format('Y-m-d'));

        self::assertEquals(1947.99, $entries[0]->getRate());
        self::assertEquals(100, $entries[1]->getRate());
        self::assertEquals(84.75, $entries[2]->getRate());
        self::assertEquals(84, $entries[3]->getRate());
        self::assertEquals(404.38, $entries[4]->getRate());
    }

    public function testDescriptionByProject(): void
    {
        $this->assertDescription($this->getCalculator(), true, false);
    }
}

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
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Invoice\Calculator\ShortInvoiceCalculator;
use App\Invoice\CalculatorInterface;
use App\Invoice\InvoiceItem;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;

/**
 * @covers \App\Invoice\Calculator\ShortInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class ShortInvoiceCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new ShortInvoiceCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $project = new Project();
        $project->setName('sdfsdf');

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setHourlyRate(293.27)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2018-11-29'))
            ->setEnd(new \DateTime())
            ->addTag((new Tag())->setName('foo'))
            ->addTag((new Tag())->setName('bar'))
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(32.59)
            ->setHourlyRate(293.27)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2018-11-28'))
            ->setEnd(new \DateTime())
            ->addTag((new Tag())->setName('bar1'))
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(146.64)
            ->setHourlyRate(293.27)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime('2018-11-29'))
            ->setEnd(new \DateTime())
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3];

        $query = new InvoiceQuery();
        $query->addActivity($activity);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('short', $sut->getId());
        self::assertEquals(562.28, $sut->getTotal());
        self::assertEquals(19, $sut->getVat());
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(472.5, $sut->getSubtotal());
        self::assertEquals(5800, $sut->getTimeWorked());
        self::assertEquals(1, \count($sut->getEntries()));

        $entries = $sut->getEntries();
        self::assertCount(1, $entries);
        $result = $entries[0];

        self::assertEquals('2018-11-28', $result->getBegin()?->format('Y-m-d'));
        self::assertEquals('', $result->getDescription());
        self::assertEquals(293.27, $result->getHourlyRate());
        self::assertNull($result->getFixedRate());
        self::assertEquals(472.5, $result->getRate());
        self::assertEquals(5800, $result->getDuration());
        self::assertEquals(3, $result->getAmount());
        self::assertEquals(['foo', 'bar', 'bar1'], $result->getTags());
    }

    public function testWithMultipleEntriesDifferentRates(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $project = new Project();
        $project->setName('sdfsdf');

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setHourlyRate(293.27)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setRate(84)
            ->setHourlyRate(756.00)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setHourlyRate(222.22)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3];

        $query = new InvoiceQuery();
        $query->addActivity($activity);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('short', $sut->getId());
        self::assertEquals(581.17, $sut->getTotal());
        self::assertEquals(19, $sut->getVat());
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(488.38, $sut->getSubtotal());
        self::assertEquals(5800, $sut->getTimeWorked());
        self::assertEquals(1, \count($sut->getEntries()));

        /** @var InvoiceItem $result */
        $result = $sut->getEntries()[0];
        self::assertNull($result->getDescription());
        self::assertEquals(488.38, $result->getHourlyRate());
        self::assertEquals(488.38, $result->getFixedRate());
        self::assertEquals(488.38, $result->getRate());
        self::assertEquals(5800, $result->getDuration());
        self::assertEquals(1, $result->getAmount());
    }

    public function testWithMixedRateTypes(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $project = new Project();
        $project->setName('sdfsdf');

        $activity = new Activity();
        $activity->setName('activity description');
        $activity->setProject($project);

        $timesheet = new Timesheet();
        $timesheet
            ->setDuration(3600)
            ->setRate(293.27)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet2 = new Timesheet();
        $timesheet2
            ->setDuration(400)
            ->setFixedRate(84)
            ->setRate(84)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $timesheet3 = new Timesheet();
        $timesheet3
            ->setDuration(1800)
            ->setRate(111.11)
            ->setUser(new User())
            ->setActivity($activity)
            ->setProject($project)
            ->setBegin(new \DateTime())
            ->setEnd(new \DateTime())
        ;

        $entries = [$timesheet, $timesheet2, $timesheet3];

        $query = new InvoiceQuery();
        $query->addActivity($activity);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('short', $sut->getId());
        self::assertEquals(581.17, $sut->getTotal());
        self::assertEquals(19, $sut->getVat());
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(488.38, $sut->getSubtotal());
        self::assertEquals(5800, $sut->getTimeWorked());
        self::assertEquals(1, \count($sut->getEntries()));

        /** @var InvoiceItem $result */
        $result = $sut->getEntries()[0];
        self::assertNull($result->getDescription());
        self::assertEquals(488.38, $result->getHourlyRate());
        self::assertEquals(488.38, $result->getRate());
        self::assertEquals(5800, $result->getDuration());
        self::assertEquals(488.38, $result->getFixedRate());
        self::assertEquals(1, $result->getAmount());
    }

    public function testDescriptionByTimesheet(): void
    {
        $this->assertDescription($this->getCalculator(), false, false);
    }
}

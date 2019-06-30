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
use App\Invoice\Calculator\ShortInvoiceCalculator;
use App\Model\InvoiceModel;
use App\Repository\Query\InvoiceQuery;

/**
 * @covers \App\Invoice\Calculator\ShortInvoiceCalculator
 * @covers \App\Invoice\Calculator\AbstractMergedCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class ShortInvoiceCalculatorTest extends AbstractCalculatorTest
{
    public function testEmptyModel()
    {
        $this->assertEmptyModel(new ShortInvoiceCalculator());
    }

    public function testWithMultipleEntries()
    {
        $customer = new Customer();
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
        $query->setActivity($activity);

        $model = new InvoiceModel();
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->setEntries($entries);
        $model->setQuery($query);

        $sut = new ShortInvoiceCalculator();
        $sut->setModel($model);

        $this->assertEquals('short', $sut->getId());
        $this->assertEquals(581.17, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $sut->getCurrency());
        $this->assertEquals(488.38, $sut->getSubtotal());
        $this->assertEquals(5800, $sut->getTimeWorked());
        $this->assertEquals(1, count($sut->getEntries()));

        /** @var Timesheet $result */
        $result = $sut->getEntries()[0];
        $this->assertEquals('activity description', $result->getDescription());
        $this->assertEquals(488.38, $result->getHourlyRate());
        $this->assertEquals(488.38, $result->getRate());
        $this->assertEquals(488.38, $result->getFixedRate());
        $this->assertEquals(5800, $result->getDuration());
    }

    public function testDescriptionByTimesheet()
    {
        $this->assertDescription(new ShortInvoiceCalculator(), false, false);
    }

    public function testDescriptionByActivity()
    {
        $this->assertDescription(new ShortInvoiceCalculator(), false, true);
    }

    public function testDescriptionByProject()
    {
        $this->assertDescription(new ShortInvoiceCalculator(), true, false);
    }
}

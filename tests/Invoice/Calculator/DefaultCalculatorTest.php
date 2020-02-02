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
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;

/**
 * @covers \App\Invoice\Calculator\DefaultCalculator
 * @covers \App\Invoice\Calculator\AbstractCalculator
 */
class DefaultCalculatorTest extends AbstractCalculatorTest
{
    public function testEmptyModel()
    {
        $this->assertEmptyModel(new DefaultCalculator());
    }

    public function testWithMultipleEntries()
    {
        $customer = new Customer();
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $timesheet = new Timesheet();
        $timesheet->setDescription('foo 1');
        $timesheet->setBegin(new \DateTime());
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setActivity(new Activity());

        $timesheet2 = new Timesheet();
        $timesheet2->setDescription('foo 2');
        $timesheet2->setBegin(new \DateTime());
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84);
        $timesheet2->setActivity(new Activity());

        $timesheet3 = new Timesheet();
        $timesheet3->setDescription('foo 3');
        $timesheet3->setBegin(new \DateTime());
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setActivity(new Activity());

        $entries = [$timesheet, $timesheet2, $timesheet3];

        $model = new InvoiceModel(new DebugFormatter());
        $model->setCustomer($customer);
        $model->setTemplate($template);
        $model->addEntries($entries);
        $model->setQuery(new InvoiceQuery());

        $sut = new DefaultCalculator();
        $sut->setModel($model);

        $this->assertEquals('default', $sut->getId());
        $this->assertEquals(581.17, $sut->getTotal());
        $this->assertEquals(19, $sut->getVat());
        $this->assertEquals('EUR', $sut->getCurrency());
        $this->assertEquals(488.38, $sut->getSubtotal());
        $this->assertEquals(5800, $sut->getTimeWorked());
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Repository\Query\InvoiceQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceModel
 */
class InvoiceModelTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new InvoiceModel();
        $this->assertNull($sut->getNumberGenerator());
        $this->assertNull($sut->getCalculator());
        $this->assertNull($sut->getTemplate());
        $this->assertNull($sut->getCustomer());
        $this->assertNull($sut->getQuery());
        $this->assertNull($sut->getDueDate());
        $this->assertEmpty($sut->getEntries());
        $this->assertIsArray($sut->getEntries());
        $this->assertInstanceOf(\DateTime::class, $sut->getInvoiceDate());
    }

    public function testSetter()
    {
        $sut = new InvoiceModel();

        $sut->setTemplate((new InvoiceTemplate())->setDueDays(10));
        $sut->setCustomer(new Customer());
        $sut->setQuery(new InvoiceQuery());
        $sut->setEntries([new Timesheet()]);
        $sut->setNumberGenerator(new DateNumberGenerator());
        $sut->setCalculator(new DefaultCalculator());

        $this->assertInstanceOf(DateNumberGenerator::class, $sut->getNumberGenerator());
        $this->assertInstanceOf(DefaultCalculator::class, $sut->getCalculator());
        $this->assertInstanceOf(InvoiceTemplate::class, $sut->getTemplate());
        $this->assertInstanceOf(Customer::class, $sut->getCustomer());
        $this->assertInstanceOf(InvoiceQuery::class, $sut->getQuery());
        $this->assertInstanceOf(\DateTime::class, $sut->getDueDate());
        $this->assertInstanceOf(Timesheet::class, $sut->getEntries()[0]);
        $this->assertInstanceOf(\DateTime::class, $sut->getInvoiceDate());

        $this->assertEquals(
            (new \DateTime('+10 days'))->format('Y-m-d'),
            $sut->getDueDate()->format('Y-m-d')
        );
    }
}

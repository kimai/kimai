<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Timesheet;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\NumberGenerator\IncrementingNumberGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceModel
 */
class InvoiceModelTest extends TestCase
{
    public function testEmptyObject()
    {
        $formatter = new DebugFormatter();
        $sut = new InvoiceModel($formatter);

        self::assertNull($sut->getQuery());
        self::assertNull($sut->getCustomer());
        self::assertNull($sut->getDueDate());
        self::assertNull($sut->getCalculator());
        self::assertNull($sut->getNumberGenerator());

        self::assertEmpty($sut->getEntries());
        self::assertIsArray($sut->getEntries());

        self::assertNull($sut->getTemplate());
        self::assertInstanceOf(\DateTime::class, $sut->getInvoiceDate());

        self::assertSame($formatter, $sut->getFormatter());
    }

    public function testEmptyObjectThrowsExceptionOnNumberGenerator()
    {
        $formatter = new DebugFormatter();
        $sut = new InvoiceModel($formatter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('InvoiceModel::getInvoiceNumber() cannot be called before calling setNumberGenerator()');
        $sut->getInvoiceNumber();
    }

    public function testSetter()
    {
        $sut = new InvoiceModel(new DebugFormatter());

        $query = new InvoiceQuery();
        self::assertInstanceOf(InvoiceModel::class, $sut->setQuery($query));
        self::assertSame($query, $sut->getQuery());

        $customer = new Customer();
        self::assertInstanceOf(InvoiceModel::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        $calculator = new DefaultCalculator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setCalculator($calculator));
        self::assertSame($calculator, $sut->getCalculator());

        $generator = new IncrementingNumberGenerator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setNumberGenerator($generator));
        self::assertSame($generator, $sut->getNumberGenerator());
        $number = $sut->getInvoiceNumber();
        $first = $sut->getNumberGenerator()->getInvoiceNumber();
        $second = $sut->getNumberGenerator()->getInvoiceNumber();
        self::assertEquals(((int) $first + 1), $second);
        self::assertEquals($number, $sut->getInvoiceNumber());

        $template = new InvoiceTemplate();
        self::assertNull($sut->getDueDate());
        self::assertInstanceOf(InvoiceModel::class, $sut->setTemplate($template));
        self::assertSame($template, $sut->getTemplate());
        self::assertInstanceOf(\DateTime::class, $sut->getDueDate());
    }

    /**
     * @group legacy
     */
    public function testDeprecations()
    {
        $sut = new InvoiceModel(new DebugFormatter());

        $entries = [new Timesheet()];
        self::assertInstanceOf(InvoiceModel::class, $sut->setEntries($entries));
        self::assertSame($entries, $sut->getEntries());
    }
}

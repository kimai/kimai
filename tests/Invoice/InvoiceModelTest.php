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
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\InvoiceModel;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\NumberGenerator\IncrementingNumberGenerator;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceModel
 */
class InvoiceModelTest extends TestCase
{
    public function testEmptyObject()
    {
        $formatter = new DebugFormatter();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel($formatter);

        self::assertNull($sut->getQuery());
        self::assertNull($sut->getCustomer());
        self::assertNull($sut->getDueDate());
        self::assertNull($sut->getCalculator());

        self::assertEmpty($sut->getEntries());
        self::assertIsArray($sut->getEntries());

        self::assertNull($sut->getTemplate());
        self::assertInstanceOf(\DateTime::class, $sut->getInvoiceDate());

        self::assertSame($formatter, $sut->getFormatter());

        $newFormatter = new DebugFormatter();
        $sut->setFormatter($newFormatter);
        self::assertNotSame($formatter, $sut->getFormatter());
        self::assertSame($newFormatter, $sut->getFormatter());
    }

    public function testEmptyObjectThrowsExceptionOnNumberGenerator()
    {
        $formatter = new DebugFormatter();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel($formatter);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('InvoiceModel::getInvoiceNumber() cannot be called before calling setNumberGenerator()');
        $sut->getInvoiceNumber();
    }

    public function testSetter()
    {
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());

        $query = new InvoiceQuery();
        self::assertInstanceOf(InvoiceModel::class, $sut->setQuery($query));
        self::assertSame($query, $sut->getQuery());

        $customer = new Customer('foo');
        self::assertInstanceOf(InvoiceModel::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        $calculator = new DefaultCalculator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setCalculator($calculator));
        self::assertSame($calculator, $sut->getCalculator());

        $generator = new IncrementingNumberGenerator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setNumberGenerator($generator));
        $number = $sut->getInvoiceNumber();
        self::assertEquals($number, $sut->getInvoiceNumber());

        $template = new InvoiceTemplate();
        self::assertNull($sut->getDueDate());
        self::assertInstanceOf(InvoiceModel::class, $sut->setTemplate($template));
        self::assertSame($template, $sut->getTemplate());
        /* @phpstan-ignore-next-line */
        self::assertInstanceOf(\DateTime::class, $sut->getDueDate());
    }
}

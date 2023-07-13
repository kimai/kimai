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
    public function testEmptyObject(): void
    {
        $formatter = new DebugFormatter();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel($formatter, new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery());

        self::assertNotNull($sut->getQuery());
        self::assertNotNull($sut->getCustomer());
        self::assertNotNull($sut->getTemplate());

        self::assertNull($sut->getCalculator());
        self::assertEmpty($sut->getEntries());
        self::assertIsArray($sut->getEntries());
        self::assertInstanceOf(\DateTimeInterface::class, $sut->getInvoiceDate());

        self::assertSame($formatter, $sut->getFormatter());

        $newFormatter = new DebugFormatter();
        $sut->setFormatter($newFormatter);
        self::assertNotSame($formatter, $sut->getFormatter());
        self::assertSame($newFormatter, $sut->getFormatter());
    }

    public function testEmptyObjectThrowsExceptionOnNumberGenerator(): void
    {
        $formatter = new DebugFormatter();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel($formatter, new Customer('foo'), new InvoiceTemplate(), new InvoiceQuery());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('InvoiceModel::getInvoiceNumber() cannot be called before calling setNumberGenerator()');
        $sut->getInvoiceNumber();
    }

    public function testSetter(): void
    {
        $customer = new Customer('foo');
        $query = new InvoiceQuery();
        $template = new InvoiceTemplate();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);

        self::assertSame($query, $sut->getQuery());

        $calculator = new DefaultCalculator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setCalculator($calculator));
        self::assertSame($calculator, $sut->getCalculator());

        $generator = new IncrementingNumberGenerator();
        self::assertInstanceOf(InvoiceModel::class, $sut->setNumberGenerator($generator));
        $number = $sut->getInvoiceNumber();
        self::assertEquals($number, $sut->getInvoiceNumber());

        self::assertSame($template, $sut->getTemplate());
        self::assertInstanceOf(\DateTimeInterface::class, $sut->getDueDate());
    }

    public function testDueDate(): void
    {
        $customer = new Customer('foo');
        $query = new InvoiceQuery();
        $template = new InvoiceTemplate();
        $sut = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);

        $dueDate = $sut->getDueDate();
        $dueDays = $template->getDueDays();
        self::assertNotNull($dueDays);
        $expected = new \DateTimeImmutable('+' . $dueDays . ' days');

        self::assertEquals($expected->format('Y-m-d'), $dueDate->format('Y-m-d'));

        $sut->setInvoiceDate(new \DateTimeImmutable('2022-05-23'));
        $template->setDueDays(14);
        $dueDate = $sut->getDueDate();
        $expected = new \DateTimeImmutable('2022-06-06');
        self::assertEquals($expected->format('Y-m-d'), $dueDate->format('Y-m-d'));
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Invoice\InvoiceItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceItem::class)]
class InvoiceItemTest extends TestCase
{
    public function testEmptyObject(): void
    {
        $sut = new InvoiceItem();

        self::assertFalse($sut->isFixedRate());
        self::assertNull($sut->getHourlyRate());
        self::assertNull($sut->getFixedRate());
        self::assertNull($sut->getEnd());
        self::assertEquals(0.00, $sut->getRate());
        self::assertEquals(0.00, $sut->getInternalRate()); // @phpstan-ignore method.deprecated
        self::assertEquals(0.00, $sut->getAppliedRate());
        self::assertNull($sut->getProject());
        self::assertIsArray($sut->getAdditionalFields());
        self::assertEmpty($sut->getAdditionalFields());
        self::assertNull($sut->getAdditionalField('foo'));
        self::assertEquals('bar', $sut->getAdditionalField('foo', 'bar'));
        self::assertNull($sut->getAdditionalField('foo'));
        self::assertInstanceOf(InvoiceItem::class, $sut->addAdditionalField('foo', 'bar2'));
        self::assertEquals('bar2', $sut->getAdditionalField('foo'));
        self::assertEquals(0, $sut->getAmount());
        self::assertNull($sut->getBegin());
        self::assertNull($sut->getActivity());
        self::assertNull($sut->getUser());
        self::assertNull($sut->getDescription());
        self::assertEquals(0, $sut->getDuration());
        self::assertNull($sut->getCategory());
        self::assertNull($sut->getType());
        self::assertEquals([], $sut->getTags());
        $sut->addTag('foo');
        $sut->addTag('foo');
        $sut->addTag('foo1');
        $sut->addTag('BaR');
        $sut->addTag('bar');
        $sut->addTag('FOO');
        $sut->addTag('bar');
        $sut->addTag('foo1');
        self::assertEquals(['foo', 'foo1', 'BaR'], $sut->getTags());
    }

    public function testRates(): void
    {
        $sut = new InvoiceItem();

        self::assertEquals(0.00, $sut->getHourlyRate());
        self::assertNull($sut->getFixedRate());
        self::assertEquals(0.00, $sut->getAppliedRate());

        $sut->setHourlyRate(13.50);
        self::assertEquals(13.50, $sut->getHourlyRate());
        self::assertNull($sut->getFixedRate());
        self::assertEquals(13.50, $sut->getAppliedRate());

        $sut->setFixedRate(30.24);
        self::assertEquals(13.50, $sut->getHourlyRate());
        self::assertEquals(30.24, $sut->getFixedRate());
        self::assertEquals(30.24, $sut->getAppliedRate());
    }
}

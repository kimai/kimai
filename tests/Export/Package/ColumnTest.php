<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package;

use App\Entity\ExportableItem;
use App\Entity\Timesheet;
use App\Export\Package\CellFormatter\CellFormatterInterface;
use App\Export\Package\CellFormatter\DateFormatter;
use App\Export\Package\Column;
use App\Export\Package\ColumnWidth;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Column::class)]
class ColumnTest extends TestCase
{
    public function testDefaults(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        self::assertEquals('testName', $column->getName());
        self::assertNull($column->getFormat());
        self::assertEquals('testName', $column->getHeader());
        self::assertSame(ColumnWidth::DEFAULT, $column->getColumnWidth());

        $column->withColumnWidth(ColumnWidth::LARGE);
        self::assertSame(ColumnWidth::LARGE, $column->getColumnWidth());
    }

    public function testThrowsOnMissiungExtractor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing extractor on column: testName');

        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        self::assertEquals('testName', $column->getValue(new Timesheet()));
    }

    public function withHeaderSetsHeader(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        $column->withHeader('testHeader');
        self::assertEquals('testHeader', $column->getHeader());
    }

    public function withExtractorSetsExtractor(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        $extractor = function (ExportableItem $item) {
            return $item->getId();
        };
        $column->withExtractor($extractor);
        $exportableItem = $this->createMock(ExportableItem::class);
        $exportableItem->method('getId')->willReturn(123);
        self::assertEquals(123, $column->extract($exportableItem));
    }

    public function testExtractThrowsExceptionWhenExtractorIsNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing extractor on column: testName');

        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        $exportableItem = $this->createMock(ExportableItem::class);
        $column->extract($exportableItem);
    }

    public function testGetValueReturnsFormattedValue(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $formatter->method('formatValue')->willReturn('formattedValue');
        $column = new Column('testName', $formatter);
        $extractor = function (ExportableItem $item) {
            return 'rawValue';
        };
        $column->withExtractor($extractor);
        $exportableItem = $this->createMock(ExportableItem::class);
        self::assertEquals('formattedValue', $column->getValue($exportableItem));
    }

    public function testGetHeaderReturnsHeaderWhenSet(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        $column->withHeader('testHeader');
        self::assertEquals('testHeader', $column->getHeader());
    }

    public function testGetHeaderReturnsNameWhenHeaderIsNull(): void
    {
        $formatter = $this->createMock(CellFormatterInterface::class);
        $column = new Column('testName', $formatter);
        self::assertEquals('testName', $column->getHeader());
    }

    public function testWithFormatReturnsValueFromFormatter(): void
    {
        $column = new Column('testName', new DateFormatter());
        self::assertEquals('yyyy-mm-dd', $column->getFormat());
    }
}

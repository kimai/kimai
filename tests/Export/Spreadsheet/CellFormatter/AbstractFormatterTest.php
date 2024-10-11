<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PHPUnit\Framework\TestCase;

abstract class AbstractFormatterTest extends TestCase
{
    abstract protected function getFormatter(): CellFormatterInterface;

    abstract protected function getActualValue();

    abstract protected function getExpectedValue();

    public function assertCellStyle(Style $style): void
    {
    }

    public function assertCellValue(Cell $cell): void
    {
        self::assertEquals($this->getExpectedValue(), $cell->getValue());
    }

    public function testSetFormattedValue(): void
    {
        $sut = $this->getFormatter();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut->setFormattedValue($worksheet, 1, 1, $this->getActualValue());
        $cell = $worksheet->getCell([1, 1]);
        $this->assertCellValue($cell);
        $this->assertCellStyle($worksheet->getStyle(CellAddress::fromColumnAndRow(1, 1)));
    }

    public function testSetNull(): void
    {
        $sut = $this->getFormatter();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut->setFormattedValue($worksheet, 1, 1, null);
        $cell = $worksheet->getCell([1, 1]);
        $this->assertNullValue($cell);
    }

    public function assertNullValue(Cell $cell): void
    {
        self::assertEquals('', $cell->getValue());
    }
}

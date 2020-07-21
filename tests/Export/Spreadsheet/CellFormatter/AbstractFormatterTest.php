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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PHPUnit\Framework\TestCase;

abstract class AbstractFormatterTest extends TestCase
{
    abstract protected function getFormatter(): CellFormatterInterface;

    abstract protected function getActualValue();

    abstract protected function getExpectedValue();

    protected function assertCellStyle(Style $style)
    {
    }

    protected function assertCellValue(Cell $cell)
    {
        self::assertEquals($this->getExpectedValue(), $cell->getValue());
    }

    public function testSetFormattedValue()
    {
        $sut = $this->getFormatter();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut->setFormattedValue($worksheet, 1, 1, $this->getActualValue());
        $cell = $worksheet->getCellByColumnAndRow(1, 1, false);
        $this->assertCellValue($cell);
        $this->assertCellStyle($worksheet->getStyleByColumnAndRow(1, 1));
    }

    public function testSetNull()
    {
        $sut = $this->getFormatter();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut->setFormattedValue($worksheet, 1, 1, null);
        $cell = $worksheet->getCellByColumnAndRow(1, 1, false);
        $this->assertNullValue($cell);
    }

    protected function assertNullValue(Cell $cell)
    {
        self::assertEquals('', $cell->getValue());
    }
}

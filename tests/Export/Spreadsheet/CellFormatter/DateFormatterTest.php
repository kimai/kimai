<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\DateFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\DateFormatter
 */
class DateFormatterTest extends AbstractFormatterTestCase
{
    private $date;

    protected function getFormatter(): CellFormatterInterface
    {
        return new DateFormatter();
    }

    protected function getActualValue()
    {
        return $this->date = new \DateTime();
    }

    protected function getExpectedValue()
    {
        return Date::PHPToExcel($this->date);
    }

    public function testFormattedValueWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only DateTimeInterface is supported');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut = $this->getFormatter();
        $sut->setFormattedValue($worksheet, 1, 1, 'sdfsdf');
    }

    public function assertCellStyle(Style $style): void
    {
        self::assertEquals(NumberFormat::FORMAT_DATE_YYYYMMDD, $style->getNumberFormat()->getFormatCode());
    }
}

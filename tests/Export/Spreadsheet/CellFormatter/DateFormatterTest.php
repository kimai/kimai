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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\DateFormatter
 */
class DateFormatterTest extends AbstractFormatterTest
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

    public function setFormattedValueWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only DateTime is supported');
    }

    protected function assertCellStyle(Style $style)
    {
        self::assertEquals(NumberFormat::FORMAT_DATE_YYYYMMDD2, $style->getNumberFormat()->getFormatCode());
    }
}

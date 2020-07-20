<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\TimeFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\TimeFormatter
 */
class TimeFormatterTest extends AbstractFormatterTest
{
    private $date;

    protected function getFormatter(): CellFormatterInterface
    {
        return new TimeFormatter();
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
        self::assertEquals(TimeFormatter::TIME_FORMAT, $style->getNumberFormat()->getFormatCode());
    }
}

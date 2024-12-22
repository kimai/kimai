<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\DurationFormatter;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\DurationFormatter
 */
class DurationFormatterTest extends AbstractFormatterTestCase
{
    protected function getFormatter(): CellFormatterInterface
    {
        return new DurationFormatter();
    }

    protected function getActualValue()
    {
        return 3600;
    }

    protected function getExpectedValue()
    {
        return '=3600/86400';
    }

    public function testFormattedValueWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only int is supported');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut = $this->getFormatter();
        $sut->setFormattedValue($worksheet, 1, 1, 'sdfsdf');
    }

    public function assertNullValue(Cell $cell): void
    {
        self::assertEquals('=0/86400', $cell->getValue());
    }

    public function assertCellStyle(Style $style): void
    {
        self::assertEquals(DurationFormatter::DURATION_FORMAT, $style->getNumberFormat()->getFormatCode());
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\StringFormatter;
use App\Tests\Utils\StringHelperTest;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\StringFormatter
 */
class StringFormatterTest extends AbstractFormatterTestCase
{
    protected function getFormatter(): CellFormatterInterface
    {
        return new StringFormatter();
    }

    protected function getActualValue(): string
    {
        return 'a simple text';
    }

    protected function getExpectedValue(): string
    {
        return 'a simple text';
    }

    public function assertNullValue(Cell $cell): void
    {
        self::assertEquals('', $cell->getValue());
    }

    public function testFormattedValueWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only string is supported');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut = $this->getFormatter();
        $sut->setFormattedValue($worksheet, 1, 1, 4711);
    }

    public function testWithDDEPayload(): void
    {
        $sut = $this->getFormatter();

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        foreach (StringHelperTest::getDdeAttackStrings() as $attackString) {
            $value = $attackString[0];
            // PHPOffice converts that, so simply skip it
            if (!str_contains($value, "\r")) {
                $sut->setFormattedValue($worksheet, 1, 1, $value);
                $cell = $worksheet->getCell([1, 1]);
                self::assertEquals("' " . $value, $cell->getValue());
            }
        }
    }
}

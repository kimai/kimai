<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\BooleanFormatter;
use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\BooleanFormatter
 */
class BooleanFormatterTest extends AbstractFormatterTest
{
    protected function getFormatter(): CellFormatterInterface
    {
        return new BooleanFormatter();
    }

    protected function getActualValue()
    {
        return false;
    }

    protected function getExpectedValue()
    {
        return false;
    }

    public function testFormattedValueWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only boolean is supported');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $sut = $this->getFormatter();
        $sut->setFormattedValue($worksheet, 1, 1, 'sdfsdf');
    }
}

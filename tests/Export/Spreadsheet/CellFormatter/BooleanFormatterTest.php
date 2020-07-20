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

    public function setFormattedValueWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only boolean is supported');
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\CellFormatter;

use App\Export\Spreadsheet\CellFormatter\ArrayFormatter;
use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;

/**
 * @covers \App\Export\Spreadsheet\CellFormatter\ArrayFormatter
 */
class ArrayFormatterTest extends AbstractFormatterTest
{
    protected function getFormatter(): CellFormatterInterface
    {
        return new ArrayFormatter();
    }

    protected function getActualValue()
    {
        return ['test', 'foo', 'bar'];
    }

    protected function getExpectedValue()
    {
        return 'test;foo;bar';
    }

    public function setFormattedValueWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value given, only array is supported');
    }
}

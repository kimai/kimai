<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Spreadsheet\ColumnDefinition
 */
class ColumnDefinitionTest extends TestCase
{
    public function testConstruct()
    {
        $sut = new \App\Export\Spreadsheet\ColumnDefinition('foo', 'bar', function () {
            return 'hello world';
        });

        self::assertEquals('foo', $sut->getLabel());
        self::assertEquals('bar', $sut->getType());
        self::assertIsCallable($sut->getAccessor());
        self::assertEquals('hello world', \call_user_func($sut->getAccessor()));
    }
}

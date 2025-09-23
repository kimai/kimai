<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Export\Spreadsheet\ColumnDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColumnDefinition::class)]
class ColumnDefinitionTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new ColumnDefinition('foo', 'bar', function () {
            return 'hello world';
        });

        self::assertEquals('messages', $sut->getTranslationDomain());
        self::assertEquals('foo', $sut->getLabel());
        self::assertEquals('bar', $sut->getType());
        self::assertIsCallable($sut->getAccessor());
        self::assertEquals('hello world', \call_user_func($sut->getAccessor()));

        $sut->setTranslationDomain('foo');
        self::assertEquals('foo', $sut->getTranslationDomain());
    }
}

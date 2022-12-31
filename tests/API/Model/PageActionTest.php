<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Model;

use App\API\Model\PageAction;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\API\Model\PageAction
 */
class PageActionTest extends TestCase
{
    public function testEmptySettings(): void
    {
        $options = [];

        $sut = new PageAction('foo', $options);

        $obj = new \ReflectionClass($sut);

        self::assertEquals('foo', $obj->getProperty('id')->getValue($sut));
        self::assertEquals('foo', $obj->getProperty('title')->getValue($sut));
        self::assertEquals('', $obj->getProperty('url')->getValue($sut));
        self::assertEquals('', $obj->getProperty('class')->getValue($sut));
        self::assertFalse($obj->getProperty('divider')->getValue($sut));
        self::assertIsArray($obj->getProperty('attr')->getValue($sut));
    }

    public function testWithSettings(): void
    {
        $options = [
            'title' => 'bar',
            'url' => 'http://sdkfjhaslkdjfhaskljh',
            'class' => 'btn-primary',
        ];

        $sut = new PageAction('trash', $options);

        $obj = new \ReflectionClass($sut);

        self::assertEquals('trash', $obj->getProperty('id')->getValue($sut));
        self::assertEquals('bar', $obj->getProperty('title')->getValue($sut));
        self::assertEquals('http://sdkfjhaslkdjfhaskljh', $obj->getProperty('url')->getValue($sut));
        self::assertEquals('btn-primary', $obj->getProperty('class')->getValue($sut));
        self::assertTrue($obj->getProperty('divider')->getValue($sut));
        self::assertIsArray($obj->getProperty('attr')->getValue($sut));
    }

    public function testDivider(): void
    {
        $options = [
            'title' => 'bar',
            'url' => null,
            'class' => 'btn-primary',
        ];

        $sut = new PageAction('divider0', $options);

        $obj = new \ReflectionClass($sut);

        self::assertEquals('divider0', $obj->getProperty('id')->getValue($sut));
        self::assertEquals('bar', $obj->getProperty('title')->getValue($sut));
        self::assertNull($obj->getProperty('url')->getValue($sut));
        self::assertEquals('btn-primary', $obj->getProperty('class')->getValue($sut));
        self::assertTrue($obj->getProperty('divider')->getValue($sut));
        self::assertIsArray($obj->getProperty('attr')->getValue($sut));
    }
}

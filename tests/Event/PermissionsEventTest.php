<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\PermissionsEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\PermissionsEvent
 */
class PermissionsEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $sut = new PermissionsEvent();

        self::assertEmpty($sut->getPermissions());
        self::assertFalse($sut->hasSection('foo'));
        self::assertNull($sut->getSection('foo'));

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('test', 'foo'));

        $sut->addPermissions('foo', []);
        self::assertTrue($sut->hasSection('foo'));
        self::assertEquals([], $sut->getSection('foo'));
        self::assertEquals(['foo' => []], $sut->getPermissions());

        self::assertInstanceOf(PermissionsEvent::class, $sut->removeSection('foo'));
        self::assertFalse($sut->hasSection('foo'));
        self::assertNull($sut->getSection('foo'));

        $sut->addPermissions('bar', ['foo', 'hello', 'world', 'test']);
        self::assertEquals(['bar' => ['foo', 'hello', 'world', 'test']], $sut->getPermissions());

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('bar', 'xxx'));
        self::assertEquals(['foo', 'hello', 'world', 'test'], array_values($sut->getSection('bar')));

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('bar', 'foo'));
        self::assertEquals(['hello', 'world', 'test'], array_values($sut->getSection('bar')));

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('bar', 'test'));
        self::assertEquals(['hello', 'world'], array_values($sut->getSection('bar')));

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('bar', 'hello'));
        self::assertEquals(['world'], array_values($sut->getSection('bar')));

        self::assertInstanceOf(PermissionsEvent::class, $sut->removePermission('bar', 'world'));
        self::assertEquals([], array_values($sut->getSection('bar')));
    }
}

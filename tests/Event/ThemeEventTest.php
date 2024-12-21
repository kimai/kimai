<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\ThemeEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\ThemeEvent
 */
class ThemeEventTest extends TestCase
{
    public function testEmpty(): void
    {
        $sut = new ThemeEvent();
        self::assertNull($sut->getUser());
        self::assertIsArray($sut->getPayload());
        self::assertEquals('', $sut->getContent());
    }

    public function testDefaultValues(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new ThemeEvent($user);

        self::assertSame($user, $sut->getUser());
    }

    public function testGetterAndSetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $payload = ['foo' => null, '' => '', 'test' => 'test', 'class' => new \stdClass()];

        $sut = new ThemeEvent($user, $payload);
        self::assertEquals($payload, $sut->getPayload());

        $sut = new ThemeEvent($user);
        $sut->addContent('foo');
        self::assertEquals('foo', $sut->getContent());

        $sut->addContent('<script>');
        self::assertEquals('foo<script>', $sut->getContent());
    }

    /**
     * @group legacy
     */
    public function testDeprecatedStuff(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $payload = ['foo' => null, '' => '', 'test' => 'test', 'class' => new \stdClass()];

        $sut = new ThemeEvent($user, $payload);
        self::assertEquals($payload, $sut->getPayload());
    }
}

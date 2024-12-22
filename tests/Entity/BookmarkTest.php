<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Bookmark;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Bookmark
 */
class BookmarkTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Bookmark();
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertIsArray($sut->getContent());
        self::assertEquals([], $sut->getContent());
        self::assertNull($sut->getType());
        self::assertNull($sut->getUser());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Bookmark();
        $sut->setName('foo-bar');
        self::assertEquals('foo-bar', $sut->getName());

        $sut->setContent(['test' => 'foo-bar', 'hello' => 'world']);
        self::assertEquals(['test' => 'foo-bar', 'hello' => 'world'], $sut->getContent());

        $sut->setType('sdsdsd');
        self::assertEquals('sdsdsd', $sut->getType());

        $user = new User();
        $sut->setUser($user);
        self::assertEquals($user, $sut->getUser());

        $sut2 = clone $sut;
        self::assertEquals('sdsdsd', $sut2->getType());
        self::assertEquals('foo-bar', $sut2->getName());
    }
}

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
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getName());
        $this->assertIsArray($sut->getContent());
        $this->assertEquals([], $sut->getContent());
        $this->assertNull($sut->getType());
        $this->assertNull($sut->getUser());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Bookmark();
        $sut->setName('foo-bar');
        $this->assertEquals('foo-bar', $sut->getName());

        $sut->setContent(['test' => 'foo-bar', 'hello' => 'world']);
        $this->assertEquals(['test' => 'foo-bar', 'hello' => 'world'], $sut->getContent());

        $sut->setType('sdsdsd');
        $this->assertEquals('sdsdsd', $sut->getType());

        $user = new User();
        $sut->setUser($user);
        $this->assertEquals($user, $sut->getUser());

        $sut2 = clone $sut;
        $this->assertEquals('sdsdsd', $sut2->getType());
        $this->assertEquals('foo-bar', $sut2->getName());
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\CommentInterface;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

abstract class AbstractCommentEntityTest extends TestCase
{
    abstract protected function getEntity(): CommentInterface;

    public function testDefaultValues()
    {
        $sut = $this->getEntity();

        self::assertNull($sut->getId());
        self::assertNull($sut->getMessage());
        self::assertNull($sut->getCreatedBy());
        self::assertNotNull($sut->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $sut->getCreatedAt());
        self::assertFalse($sut->isPinned());
    }

    public function testSetterAndGetter()
    {
        $sut = $this->getEntity();

        $sut->setPinned(true);
        self::assertTrue($sut->isPinned());

        $user = new User();
        $sut->setCreatedBy($user);
        self::assertSame($user, $sut->getCreatedBy());

        $date = new \DateTime();
        $sut->setCreatedAt($date);
        self::assertSame($date, $sut->getCreatedAt());

        $sut->setMessage('slödkfjaölsdkjflaksjdfölaksjdfölakjsdöfl');
        self::assertEquals('slödkfjaölsdkjflaksjdfölaksjdfölakjsdöfl', $sut->getMessage());
    }
}

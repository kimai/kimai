<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Tag
 */
class TagTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new Tag();
        self::assertNull($sut->getId());
        self::assertNull($sut->getName());
        self::assertNull($sut->getColor());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new Tag();

        self::assertInstanceOf(Tag::class, $sut->setName('foo'));
        self::assertEquals('foo', $sut->getName());
        self::assertEquals('foo', (string) $sut);

        $sut->setName(null);
        self::assertNull($sut->getName());
        self::assertNull($sut->getColor());
        self::assertIsString($sut->getColorSafe());

        $sut->setColor('#fffccc');
        self::assertEquals('#fffccc', $sut->getColor());
        self::assertEquals('#fffccc', $sut->getColorSafe());
    }
}

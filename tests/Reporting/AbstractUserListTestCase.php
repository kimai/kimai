<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Reporting\AbstractUserList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\AbstractUserList
 */
abstract class AbstractUserListTestCase extends TestCase
{
    abstract protected function createSut(): AbstractUserList;

    public function testEmptyObject(): void
    {
        $sut = $this->createSut();
        self::assertNull($sut->getDate());
        self::assertEquals('duration', $sut->getSumType());
        self::assertFalse($sut->isDecimal());
    }

    public function testSetter(): void
    {
        $date = new \DateTime('2019-05-27');

        $sut = $this->createSut();
        $sut->setDate($date);

        self::assertSame($date, $sut->getDate());

        $sut->setSumType('rate');
        self::assertEquals('rate', $sut->getSumType());

        $sut->setSumType('internalRate');
        self::assertEquals('internalRate', $sut->getSumType());

        $sut->setSumType('duration');
        self::assertEquals('duration', $sut->getSumType());

        $sut->setDecimal(true);
        self::assertTrue($sut->isDecimal());

        $sut->setDecimal(false);
        self::assertFalse($sut->isDecimal());
    }

    public function testInvalidSumType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sut = $this->createSut();
        $sut->setSumType('DURation');
    }
}

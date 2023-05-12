<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\Day;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\Day
 */
class DayTest extends TestCase
{
    public function testDefaults(): void
    {
        $date = new \DateTimeImmutable('2023-07-21');
        $sut = new Day($date);
        self::assertSame($date, $sut->getDay());
    }
}

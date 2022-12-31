<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model;

use App\Model\QuickEntryModel;
use App\Model\QuickEntryWeek;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\QuickEntryWeek
 */
class QuickEntryWeekTest extends TestCase
{
    public function testModel(): void
    {
        $date = new \DateTime();

        $sut = new QuickEntryWeek($date);
        self::assertSame($date, $sut->getDate());
        self::assertEquals([], $sut->getRows());

        $rows = [
            new QuickEntryModel()
        ];

        $sut->setRows($rows);
        self::assertSame($rows, $sut->getRows());
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Model\Statistic;

use App\Model\Statistic\Month;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Model\Statistic\Month
 */
class MonthTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new Month('01');
        self::assertSame('01', $sut->getMonth());
        self::assertSame(0, $sut->getTotalDuration());
        self::assertSame(0.0, $sut->getTotalRate());
        self::assertSame(0, $sut->getBillableDuration());
        self::assertSame(0.0, $sut->getBillableRate());
    }

    public function testAllowedMonths()
    {
        for ($i = 1; $i < 10; $i++) {
            new Month('0' . $i);
        }
        for ($i = 10; $i < 13; $i++) {
            new Month((string) $i);
        }
        self::assertTrue(true);
    }

    public function testInvalidMonths()
    {
        foreach (['00', '13', '99', '0.9'] as $month) {
            $ex = null;
            try {
                new Month($month);
            } catch (Exception $e) {
                $ex = $e;
            }
            self::assertInstanceOf(InvalidArgumentException::class, $ex);
            self::assertEquals(
                'Invalid month given. Expected 1-12, received "' . ((int) $month) . '".',
                $ex->getMessage()
            );
        }
    }

    public function testSetter()
    {
        $sut = new Month('01');
        $sut->setTotalDuration(999);
        $sut->setTotalRate(0.123456789);
        $sut->setBillableDuration(123456);
        $sut->setBillableRate(123.456789);

        self::assertSame(999, $sut->getTotalDuration());
        self::assertSame(0.123456789, $sut->getTotalRate());
        self::assertSame(123456, $sut->getBillableDuration());
        self::assertSame(123.456789, $sut->getBillableRate());
    }
}

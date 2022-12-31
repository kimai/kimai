<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Annotation;

use App\Export\Annotation\Order;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Annotation\Order
 */
class OrderTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new Order();

        self::assertEquals([], $sut->order);
    }

    public function testWithValues(): void
    {
        $sut = new Order(['foo' => 'test', 'bla' => 123]);

        self::assertEquals(['foo' => 'test', 'bla' => 123], $sut->order);
    }
}

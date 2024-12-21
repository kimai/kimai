<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\GoogleSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\GoogleSource
 */
class GoogleSourceTest extends TestCase
{
    public function testConstruct(): void
    {
        $sut = new GoogleSource('0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', '#fffccc');

        self::assertEquals('0815', $sut->getId());
        self::assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        self::assertEquals('#fffccc', $sut->getColor());

        $sut = new GoogleSource('0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', null);

        self::assertEquals('0815', $sut->getId());
        self::assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        self::assertNull($sut->getColor());
    }
}

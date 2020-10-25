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
    public function testConstruct()
    {
        $sut = new GoogleSource('0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', '#fffccc');

        $this->assertEquals('0815', $sut->getId());
        $this->assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        $this->assertEquals('#fffccc', $sut->getColor());

        $sut = new GoogleSource('0815', 'askdjfhlaksjdhflaksjhdflkjasdlkfjh', null);

        $this->assertEquals('0815', $sut->getId());
        $this->assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
        $this->assertNull($sut->getColor());
    }
}

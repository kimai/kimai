<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\Source;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Calendar\Source
 */
class SourceTest extends TestCase
{
    public function testConstruct()
    {
        $sut = new Source();

        $this->assertNull($sut->getId());
        $this->assertNull($sut->getColor());
        $this->assertNull($sut->getUri());

        $this->assertInstanceOf(Source::class, $sut->setId('0815'));
        $this->assertInstanceOf(Source::class, $sut->setColor('#fffccc'));
        $this->assertInstanceOf(Source::class, $sut->setUri('askdjfhlaksjdhflaksjhdflkjasdlkfjh'));

        $this->assertEquals('0815', $sut->getId());
        $this->assertEquals('#fffccc', $sut->getColor());
        $this->assertEquals('askdjfhlaksjdhflaksjhdflkjasdlkfjh', $sut->getUri());
    }
}

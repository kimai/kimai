<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\WorkingTime\Model\BoxConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\BoxConfiguration
 */
class BoxConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new BoxConfiguration();
        self::assertFalse($sut->isDecimal());
        self::assertFalse($sut->isCollapsed());
    }

    public function testSetter(): void
    {
        $sut = new BoxConfiguration();
        $sut->setDecimal(true);
        self::assertTrue($sut->isDecimal());
        self::assertFalse($sut->isCollapsed());
        $sut->setCollapsed(true);
        self::assertTrue($sut->isDecimal());
        self::assertTrue($sut->isCollapsed());
    }
}

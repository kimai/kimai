<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Model;

use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\Model\SystemConfiguration
 */
class SystemConfigurationTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new SystemConfiguration();
        self::assertNull($sut->getSection());
        self::assertEquals([], $sut->getConfiguration());
    }

    public function testSetterAndGetter()
    {
        $sut = new SystemConfiguration();

        self::assertInstanceOf(SystemConfiguration::class, $sut->setSection('foo'));
        self::assertEquals('foo', $sut->getSection());

        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([]));
        self::assertEquals([], $sut->getConfiguration());

        $config = new Configuration();
        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([$config]));
        self::assertEquals([$config], $sut->getConfiguration());

        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([$config, $config]));
        self::assertEquals([$config, $config], $sut->getConfiguration());

        self::assertInstanceOf(SystemConfiguration::class, $sut->addConfiguration($config));
        self::assertEquals([$config, $config, $config], $sut->getConfiguration());
    }
}

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Configuration::class)]
#[CoversClass(SystemConfiguration::class)]
class SystemConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new SystemConfiguration();
        self::assertNull($sut->getSection());
        self::assertEquals([], $sut->getConfiguration());
    }

    public function testSetterAndGetter(): void
    {
        $sut = new SystemConfiguration('foo');

        self::assertEquals('foo', $sut->getSection());

        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([]));
        self::assertEquals([], $sut->getConfiguration());

        $config = new Configuration('1');
        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([$config]));
        self::assertEquals([$config], $sut->getConfiguration());

        self::assertInstanceOf(SystemConfiguration::class, $sut->setConfiguration([$config, $config]));
        self::assertEquals([$config, $config], $sut->getConfiguration());

        self::assertInstanceOf(SystemConfiguration::class, $sut->addConfiguration($config));
        self::assertEquals([$config, $config, $config], $sut->getConfiguration());

        $config = new Configuration('foo');
        $sut->addConfiguration($config);

        $config2 = new Configuration('bar');
        $sut->addConfiguration($config2);

        self::assertSame($config, $sut->getConfigurationByName('foo'));
        self::assertNull($sut->getConfigurationByName('bar2'));
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\Configuration;
use App\Event\SystemConfigurationEvent;
use App\Form\Model\SystemConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\SystemConfigurationEvent
 */
class SystemConfigurationEventTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new SystemConfigurationEvent([]);
        self::assertIsArray($sut->getConfigurations());
        self::assertEmpty($sut->getConfigurations());
        self::assertInstanceOf(SystemConfigurationEvent::class, $sut->addConfiguration(new SystemConfiguration()));
        self::assertCount(1, $sut->getConfigurations());

        $config = new Configuration();
        $config->setName('foo')->setValue('bar');
        $sysConfig = new SystemConfiguration();
        $sysConfig->setConfiguration([$config]);
        $sut = new SystemConfigurationEvent([$sysConfig]);
        self::assertEquals([$sysConfig], $sut->getConfigurations());
    }
}

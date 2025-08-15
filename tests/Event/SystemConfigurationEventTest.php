<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SystemConfigurationEvent::class)]
class SystemConfigurationEventTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $sut = new SystemConfigurationEvent([]);
        self::assertIsArray($sut->getConfigurations());
        self::assertEmpty($sut->getConfigurations());
        self::assertInstanceOf(SystemConfigurationEvent::class, $sut->addConfiguration(new SystemConfiguration()));
        self::assertCount(1, $sut->getConfigurations());

        $config = new Configuration('foo');
        $config->setValue('bar');
        $sysConfig = new SystemConfiguration();
        $sysConfig->setConfiguration([$config]);
        $sut = new SystemConfigurationEvent([$sysConfig]);
        self::assertEquals([$sysConfig], $sut->getConfigurations());
    }
}

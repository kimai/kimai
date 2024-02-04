<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\LdapConfiguration;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\LdapConfiguration
 * @covers \App\Configuration\SystemConfiguration
 */
class LdapConfigurationTest extends TestCase
{
    protected function getSut(array $settings)
    {
        $systemConfig = SystemConfigurationFactory::create(new TestConfigLoader([]), ['ldap' => $settings]);

        return new LdapConfiguration($systemConfig);
    }

    protected function getDefaultSettings()
    {
        return [
            'activate' => true,
            'connection' => [
                'host' => '1.2.3.4',
            ],
            'user' => [
                'foo' => 'bar',
            ],
            'role' => [
                'bar' => 'foo',
            ],
        ];
    }

    public function testDefault(): void
    {
        $sut = $this->getSut([]);
        $this->assertFalse($sut->isActivated());
        $this->assertEquals([], $sut->getUserParameters());
        $this->assertEquals([], $sut->getRoleParameters());
        $this->assertEquals([], $sut->getConnectionParameters());
    }

    public function testMapping(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertTrue($sut->isActivated());
        $this->assertEquals(['foo' => 'bar'], $sut->getUserParameters());
        $this->assertEquals(['bar' => 'foo'], $sut->getRoleParameters());
        $this->assertEquals(['host' => '1.2.3.4'], $sut->getConnectionParameters());
    }
}

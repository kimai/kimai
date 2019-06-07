<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\LdapConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\LdapConfiguration
 */
class LdapConfigurationTest extends TestCase
{
    protected function getSut(array $settings)
    {
        return new LdapConfiguration($settings);
    }

    protected function getDefaultSettings()
    {
        return [
            'active' => true,
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

    public function testMapping()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertTrue($sut->isActivated());
        $this->assertEquals(['foo' => 'bar'], $sut->getUserParameters());
        $this->assertEquals(['bar' => 'foo'], $sut->getRoleParameters());
        $this->assertEquals(['host' => '1.2.3.4'], $sut->getConnectionParameters());

        $sut = $this->getSut(['active' => false]);
        $this->assertFalse($sut->isActivated());
    }
}

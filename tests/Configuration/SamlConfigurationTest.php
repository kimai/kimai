<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\SamlConfiguration;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Configuration\SamlConfiguration
 * @covers \App\Configuration\SystemConfiguration
 */
class SamlConfigurationTest extends TestCase
{
    protected function getSut(array $settings)
    {
        $systemConfig = SystemConfigurationFactory::create(new TestConfigLoader([]), ['saml' => $settings]);

        return new SamlConfiguration($systemConfig);
    }

    protected function getDefaultSettings()
    {
        return [
            'activate' => true,
            'title' => 'SAML title',
            'provider' => 'google',
            'connection' => [
                'host' => '1.2.3.4',
            ],
            'mapping' => [
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => '$FirstName $LastName', 'kimai' => 'alias'],
            ],
            'roles' => [
                'attribute' => 'Roles',
                'resetOnLogin' => true,
                'mapping' => [
                    ['saml' => 'Kimai - Admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
                    ['saml' => 'Management', 'kimai' => 'ROLE_TEAMLEAD'],
                ]
            ],
        ];
    }

    public function testDefault()
    {
        $sut = $this->getSut([]);
        $this->assertFalse($sut->isActivated());
        $this->assertEquals('', $sut->getTitle());
        $this->assertEquals([], $sut->getConnection());
        $this->assertEquals([], $sut->getRolesMapping());
        $this->assertEquals('', $sut->getRolesAttribute());
        $this->assertEquals([], $sut->getAttributeMapping());
        $this->assertFalse($sut->isRolesResetOnLogin());
    }

    public function testDefaultSettings()
    {
        $sut = $this->getSut($this->getDefaultSettings());
        $this->assertTrue($sut->isActivated());
        $this->assertTrue($sut->isRolesResetOnLogin());
        $this->assertEquals('SAML title', $sut->getTitle());
        $this->assertEquals('google', $sut->getProvider());
        $this->assertEquals([
            'host' => '1.2.3.4',
        ], $sut->getConnection());
        $this->assertEquals([
            ['saml' => 'Kimai - Admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
            ['saml' => 'Management', 'kimai' => 'ROLE_TEAMLEAD'],
        ], $sut->getRolesMapping());
        $this->assertEquals('Roles', $sut->getRolesAttribute());
        $this->assertEquals([
            ['saml' => '$Email', 'kimai' => 'email'],
            ['saml' => '$FirstName $LastName', 'kimai' => 'alias'],
        ], $sut->getAttributeMapping());
    }
}

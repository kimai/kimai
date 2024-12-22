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

    public function testDefault(): void
    {
        $sut = $this->getSut([]);
        self::assertFalse($sut->isActivated());
        self::assertEquals('', $sut->getTitle());
        self::assertEquals([], $sut->getConnection());
        self::assertEquals([], $sut->getRolesMapping());
        self::assertEquals('', $sut->getRolesAttribute());
        self::assertEquals([], $sut->getAttributeMapping());
        self::assertFalse($sut->isRolesResetOnLogin());
    }

    public function testDefaultSettings(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertTrue($sut->isActivated());
        self::assertTrue($sut->isRolesResetOnLogin());
        self::assertEquals('SAML title', $sut->getTitle());
        self::assertEquals('google', $sut->getProvider());
        self::assertEquals([
            'host' => '1.2.3.4',
        ], $sut->getConnection());
        self::assertEquals([
            ['saml' => 'Kimai - Admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
            ['saml' => 'Management', 'kimai' => 'ROLE_TEAMLEAD'],
        ], $sut->getRolesMapping());
        self::assertEquals('Roles', $sut->getRolesAttribute());
        self::assertEquals([
            ['saml' => '$Email', 'kimai' => 'email'],
            ['saml' => '$FirstName $LastName', 'kimai' => 'alias'],
        ], $sut->getAttributeMapping());
    }
}

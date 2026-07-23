<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Configuration;

use App\Configuration\OidcConfiguration;
use App\Configuration\SystemConfiguration;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcConfiguration::class)]
#[CoversClass(SystemConfiguration::class)]
class OidcConfigurationTest extends TestCase
{
    protected function getSut(array $settings): OidcConfiguration
    {
        $systemConfig = SystemConfigurationFactory::create(new TestConfigLoader([]), ['oidc' => $settings]);

        return new OidcConfiguration($systemConfig);
    }

    protected function getDefaultSettings(): array
    {
        return [
            'activate' => true,
            'title' => 'OIDC title',
            'provider' => 'keycloak',
            'client_id' => 'my-client-id',
            'client_secret' => 'my-client-secret',
            'issuer' => 'https://id.example.com',
            'authorization_url' => 'https://id.example.com/authorize',
            'token_url' => 'https://id.example.com/token',
            'userinfo_url' => 'https://id.example.com/userinfo',
            'scopes' => 'openid profile email groups',
            'username_claim' => 'email',
            'mapping' => [
                ['oidc' => 'email', 'kimai' => 'email'],
                ['oidc' => 'name', 'kimai' => 'alias'],
            ],
            'roles' => [
                'claim' => 'groups',
                'resetOnLogin' => true,
                'mapping' => [
                    ['oidc' => 'kimai-admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
                    ['oidc' => 'management', 'kimai' => 'ROLE_TEAMLEAD'],
                ],
            ],
        ];
    }

    public function testDefault(): void
    {
        $sut = $this->getSut([]);
        self::assertFalse($sut->isActivated());
        self::assertEquals('', $sut->getTitle());
        self::assertNull($sut->getProvider());
        self::assertEquals('', $sut->getClientId());
        self::assertEquals('', $sut->getClientSecret());
        self::assertNull($sut->getIssuer());
        self::assertNull($sut->getAuthorizationUrl());
        self::assertNull($sut->getTokenUrl());
        self::assertNull($sut->getUserInfoUrl());
        self::assertEquals(['openid', 'profile', 'email'], $sut->getScopes());
        self::assertEquals('preferred_username', $sut->getUsernameClaim());
        self::assertEquals([], $sut->getAttributeMapping());
        self::assertNull($sut->getRolesClaim());
        self::assertEquals([], $sut->getRolesMapping());
        self::assertFalse($sut->isRolesResetOnLogin());
    }

    public function testDefaultSettings(): void
    {
        $sut = $this->getSut($this->getDefaultSettings());
        self::assertTrue($sut->isActivated());
        self::assertTrue($sut->isRolesResetOnLogin());
        self::assertEquals('OIDC title', $sut->getTitle());
        self::assertEquals('keycloak', $sut->getProvider());
        self::assertEquals('my-client-id', $sut->getClientId());
        self::assertEquals('my-client-secret', $sut->getClientSecret());
        self::assertEquals('https://id.example.com', $sut->getIssuer());
        self::assertEquals('https://id.example.com/authorize', $sut->getAuthorizationUrl());
        self::assertEquals('https://id.example.com/token', $sut->getTokenUrl());
        self::assertEquals('https://id.example.com/userinfo', $sut->getUserInfoUrl());
        self::assertEquals(['openid', 'profile', 'email', 'groups'], $sut->getScopes());
        self::assertEquals('email', $sut->getUsernameClaim());
        self::assertEquals([
            ['oidc' => 'email', 'kimai' => 'email'],
            ['oidc' => 'name', 'kimai' => 'alias'],
        ], $sut->getAttributeMapping());
        self::assertEquals('groups', $sut->getRolesClaim());
        self::assertEquals([
            ['oidc' => 'kimai-admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
            ['oidc' => 'management', 'kimai' => 'ROLE_TEAMLEAD'],
        ], $sut->getRolesMapping());
    }
}

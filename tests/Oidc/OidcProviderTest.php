<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Configuration\OidcConfiguration;
use App\Entity\User;
use App\Oidc\OidcLoginAttributes;
use App\Oidc\OidcProvider;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\User\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[CoversClass(OidcProvider::class)]
class OidcProviderTest extends TestCase
{
    protected function getOidcProvider(array $settings = null, ?User $user = null): OidcProvider
    {
        if (null === $settings) {
            $settings = [
                'mapping' => [
                    ['oidc' => 'email', 'kimai' => 'email'],
                    ['oidc' => 'name', 'kimai' => 'title'],
                ],
                'roles' => [
                    'claim' => '',
                    'mapping' => [],
                ],
            ];
        }

        $configuration = SystemConfigurationFactory::create(new TestConfigLoader([]), [
            'oidc' => $settings,
        ]);
        $oidcConfig = new OidcConfiguration($configuration);

        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->disableOriginalConstructor();
        $userProvider->onlyMethods(['refreshUser', 'supportsClass', 'loadUserByIdentifier']);
        $userProvider = $userProvider->getMock();
        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->getMock();
        if ($user !== null) {
            $userProvider->method('loadUserByIdentifier')->willReturn($user);
        } else {
            $userProvider->method('loadUserByIdentifier')->willReturn(new User());
        }

        return new OidcProvider($userService, $userProvider, $oidcConfig, $this->createMock(LoggerInterface::class));
    }

    public function testFindUserHydratesUser(): void
    {
        $user = new User();
        $user->setAuth(User::AUTH_INTERNAL);
        $user->setUserIdentifier('foo1@example.com');
        $user->setTitle('will be overwritten');

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'email' => 'foo@example.com',
            'name' => 'John Doe',
        ]);

        $sut = $this->getOidcProvider(null, $user);
        $tokenUser = $sut->findUser($token);

        self::assertSame($user, $tokenUser);
        self::assertTrue($tokenUser->isOidcUser());
        self::assertEquals('foo1@example.com', $tokenUser->getUserIdentifier());
        self::assertEquals('John Doe', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testFindUserCreatesNewUser(): void
    {
        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo2@example.com');
        $token->setAttributes([
            'email' => 'foo@example.com',
            'name' => 'Jane Doe',
        ]);

        $sut = $this->getOidcProvider(null);
        $tokenUser = $sut->findUser($token);

        self::assertTrue($tokenUser->isOidcUser());
        self::assertEquals('foo2@example.com', $tokenUser->getUserIdentifier());
        self::assertEquals('Jane Doe', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testFindUserResolvesArrayClaims(): void
    {
        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo3@example.com');
        $token->setAttributes([
            'email' => ['foo@example.com'],
            'name' => ['John Doe'],
        ]);

        $sut = $this->getOidcProvider(null);
        $tokenUser = $sut->findUser($token);

        self::assertEquals('John Doe', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testRolesMapping(): void
    {
        $settings = [
            'mapping' => [],
            'roles' => [
                'claim' => 'groups',
                'resetOnLogin' => true,
                'mapping' => [
                    ['oidc' => 'kimai-admin', 'kimai' => 'ROLE_SUPER_ADMIN'],
                    ['oidc' => 'management', 'kimai' => 'ROLE_TEAMLEAD'],
                ],
            ],
        ];

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo4@example.com');
        $token->setAttributes([
            'groups' => ['kimai-admin', 'unknown-group'],
        ]);

        $sut = $this->getOidcProvider($settings);
        $tokenUser = $sut->findUser($token);

        self::assertContains('ROLE_SUPER_ADMIN', $tokenUser->getRoles());
        self::assertNotContains('ROLE_TEAMLEAD', $tokenUser->getRoles());
    }

    public function testMissingClaimIsIgnored(): void
    {
        $settings = [
            'mapping' => [
                ['oidc' => 'email', 'kimai' => 'email'],
                ['oidc' => 'missing_claim', 'kimai' => 'title'],
            ],
            'roles' => [
                'claim' => '',
                'mapping' => [],
            ],
        ];

        $user = new User();
        $user->setAuth(User::AUTH_OIDC);
        $user->setUserIdentifier('foo5@example.com');
        $user->setTitle('I will not be overwritten');

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'email' => 'foo@example.com',
        ]);

        $sut = $this->getOidcProvider($settings, $user);
        $tokenUser = $sut->findUser($token);

        self::assertSame($user, $tokenUser);
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
        self::assertEquals('I will not be overwritten', $tokenUser->getTitle());
    }

    public function testInvalidMappingFieldThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid OIDC mapping field: notExistingField');

        $settings = [
            'mapping' => [
                ['oidc' => 'email', 'kimai' => 'notExistingField'],
            ],
            'roles' => [
                'claim' => '',
                'mapping' => [],
            ],
        ];

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo6@example.com');
        $token->setAttributes([
            'email' => 'foo@example.com',
        ]);

        $sut = $this->getOidcProvider($settings);
        $sut->findUser($token);
    }

    public function testRolesMappingWithoutResetAppendsRoles(): void
    {
        $settings = [
            'mapping' => [],
            'roles' => [
                'claim' => 'groups',
                'resetOnLogin' => false,
                'mapping' => [
                    ['oidc' => 'management', 'kimai' => 'ROLE_TEAMLEAD'],
                ],
            ],
        ];

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo7@example.com');
        $token->setAttributes([
            'groups' => 'management',
        ]);

        $sut = $this->getOidcProvider($settings);
        $tokenUser = $sut->findUser($token);

        self::assertContains('ROLE_TEAMLEAD', $tokenUser->getRoles());
        self::assertContains('ROLE_USER', $tokenUser->getRoles());
    }

    public function testNonStringClaimValueIsCastToString(): void
    {
        $settings = [
            'mapping' => [
                ['oidc' => 'employee_id', 'kimai' => 'title'],
            ],
            'roles' => [
                'claim' => '',
                'mapping' => [],
            ],
        ];

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo8@example.com');
        $token->setAttributes([
            'employee_id' => 4711,
        ]);

        $sut = $this->getOidcProvider($settings);
        $tokenUser = $sut->findUser($token);

        self::assertSame('4711', $tokenUser->getTitle());
    }

    public function testBooleanClaimValueIsIgnored(): void
    {
        $settings = [
            'mapping' => [
                ['oidc' => 'is_admin', 'kimai' => 'title'],
            ],
            'roles' => [
                'claim' => '',
                'mapping' => [],
            ],
        ];

        $user = new User();
        $user->setAuth(User::AUTH_OIDC);
        $user->setUserIdentifier('foo9@example.com');
        $user->setTitle('unchanged');

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'is_admin' => true,
        ]);

        $sut = $this->getOidcProvider($settings, $user);
        $tokenUser = $sut->findUser($token);

        self::assertSame('unchanged', $tokenUser->getTitle());
    }

    public function testFindUserThrowsAuthenticationExceptionWhenSavingFails(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed creating or hydrating user');

        $configuration = SystemConfigurationFactory::create(new TestConfigLoader([]), [
            'oidc' => [
                'mapping' => [],
                'roles' => ['claim' => '', 'mapping' => []],
            ],
        ]);
        $oidcConfig = new OidcConfiguration($configuration);

        $userProvider = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['refreshUser', 'supportsClass', 'loadUserByIdentifier'])
            ->getMock();
        $userProvider->method('loadUserByIdentifier')->willReturn(new User());

        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->getMock();
        $userService->method('saveUser')->willThrowException(new \RuntimeException('database is down'));

        $sut = new OidcProvider($userService, $userProvider, $oidcConfig, $this->createMock(LoggerInterface::class));

        $token = new OidcLoginAttributes();
        $token->setUserIdentifier('foo10@example.com');
        $token->setAttributes([
            'email' => 'foo@example.com',
        ]);

        $sut->findUser($token);
    }
}

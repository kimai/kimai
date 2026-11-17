<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Configuration\SamlConfiguration;
use App\Entity\User;
use App\Saml\SamlLoginAttributes;
use App\Saml\SamlProvider;
use App\Tests\Configuration\TestConfigLoader;
use App\Tests\Mocks\SystemConfigurationFactory;
use App\User\ExternalUserSanitizer;
use App\User\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Validator\Validation;

#[CoversClass(SamlProvider::class)]
class SamlProviderTest extends TestCase
{
    private function createUserSanitizer(): ExternalUserSanitizer
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return new ExternalUserSanitizer($validator, $this->createMock(LoggerInterface::class));
    }

    protected function getSamlProvider(array $mapping = null, ?User $user = null): SamlProvider
    {
        if (null === $mapping) {
            $mapping = [
                'mapping' => [
                    ['saml' => '$Email', 'kimai' => 'email'],
                    ['saml' => '$title', 'kimai' => 'title'],
                ],
                'roles' => [
                    'attribute' => '',
                    'mapping' => []
                ]
            ];
        }

        $configuration = SystemConfigurationFactory::create(new TestConfigLoader([]), [
            'saml' => $mapping
        ]);
        $samlConfig = new SamlConfiguration($configuration);

        // can be replaced, once loadUserByIdentifier is in the interface with SF6?
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->disableOriginalConstructor();
        $userProvider->onlyMethods(['refreshUser', 'supportsClass', 'loadUserByIdentifier']);
        $userProvider = $userProvider->getMock();
        $userService = $this->getMockBuilder(UserService::class)->disableOriginalConstructor()->getMock();
        if ($user !== null) {
            $userProvider->method('loadUserByIdentifier')->willReturn($user);
        } else {
            $userProvider->method('loadUserByIdentifier')->willReturn(new User());
        }

        $provider = new SamlProvider($userService, $userProvider, $samlConfig, $this->createUserSanitizer(), $this->createMock(LoggerInterface::class));

        return $provider;
    }

    public function testFindUserHydratesUser(): void
    {
        $user = new User();
        $user->setAuth(User::AUTH_INTERNAL);
        $user->setUserIdentifier('foo1@example.com');
        $user->setTitle('jagfkjhsgf');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'title' => ['Tralalala'],
        ]);

        $sut = $this->getSamlProvider(null, $user);
        $tokenUser = $sut->findUser($token);

        self::assertSame($user, $tokenUser);
        self::assertTrue($tokenUser->isSamlUser());
        self::assertEquals('foo1@example.com', $tokenUser->getUserIdentifier());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testFindUserCreatesNewUser(): void
    {
        $token = new SamlLoginAttributes();
        $token->setUserIdentifier('foo2@example.com');
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'title' => ['Tralalala'],
        ]);

        $sut = $this->getSamlProvider(null);
        $tokenUser = $sut->findUser($token);

        self::assertTrue($tokenUser->isSamlUser());
        self::assertEquals('foo2@example.com', $tokenUser->getUserIdentifier());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
        // a new user needs a plain password to pass the validation in UserService
        self::assertNotEmpty($tokenUser->getPlainPassword());
        self::assertSame('', $tokenUser->getPassword());
    }

    public function testFindUserIgnoresMappedPasswordAndAuth(): void
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$Email', 'kimai' => 'email'],
                // a misconfigured (or manipulated) mapping must not allow the identity
                // provider to define the local password or the authentication type
                ['saml' => '$secret', 'kimai' => 'password'],
                ['saml' => '$secret', 'kimai' => 'plainPassword'],
                ['saml' => '$kimai_auth', 'kimai' => 'auth'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier('foo3@example.com');
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'secret' => ['i-know-your-password'],
            'kimai_auth' => [User::AUTH_INTERNAL],
        ]);

        $sut = $this->getSamlProvider($mapping);
        $tokenUser = $sut->findUser($token);

        self::assertTrue($tokenUser->isSamlUser());
        self::assertSame('', $tokenUser->getPassword());
        self::assertNotEquals('i-know-your-password', $tokenUser->getPlainPassword());
    }

    public function testFindUserIgnoresMappedCredentialsForExistingSamlUser(): void
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$Email', 'kimai' => 'email'],
                // the auth does not change for existing SAML users, so the password is not reset:
                // the mapping must be ignored, independent of the case of the field name
                ['saml' => '$kimai_auth', 'kimai' => 'auth'],
                ['saml' => '$secret', 'kimai' => 'PlainPassword'],
                ['saml' => '$secret', 'kimai' => 'password'],
                ['saml' => '$secret', 'kimai' => 'apiToken'],
                ['saml' => '$secret', 'kimai' => 'totpSecret'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $user = new User();
        // an already persisted user
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, 13);
        $user->setUserIdentifier('foo3@example.com');
        $user->setAuth(User::AUTH_SAML);
        $user->setPassword('existing-hash');
        $user->eraseCredentials();

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier('foo3@example.com');
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'secret' => ['i-know-your-password'],
            'kimai_auth' => [User::AUTH_SAML],
        ]);

        $sut = $this->getSamlProvider($mapping, $user);
        $tokenUser = $sut->findUser($token);

        self::assertSame($user, $tokenUser);
        self::assertTrue($tokenUser->isSamlUser());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
        self::assertEquals('existing-hash', $tokenUser->getPassword());
        self::assertNull($tokenUser->getPlainPassword());
        self::assertNull($tokenUser->getApiToken()); // @phpstan-ignore method.deprecated
        self::assertNull($tokenUser->getTotpSecret());
    }

    public function testFindUserInvalidatesPasswordOfInternalUser(): void
    {
        // an existing internal user, which is switched to SAML on the first SAML login
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, 14);
        $user->setUserIdentifier('foo4@example.com');
        $user->setPassword('hash-of-the-internal-password');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier('foo4@example.com');
        $token->setAttributes([
            'Email' => ['foo4@example.com'],
            'title' => ['Tralalala'],
        ]);

        $sut = $this->getSamlProvider(null, $user);
        $tokenUser = $sut->findUser($token);

        self::assertTrue($tokenUser->isSamlUser());
        self::assertSame('', $tokenUser->getPassword());
        self::assertNotEmpty($tokenUser->getPlainPassword());
    }

    public function testFindUserRemovesInvalidAvatar(): void
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$avatar', 'kimai' => 'avatar'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $user = new User();
        $user->setAuth(User::AUTH_SAML);
        $user->setUserIdentifier('foo1@example.com');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        // a relative URL, which could have been set by the SAML attribute mapping
        $token->setAttributes([
            'avatar' => ['/images/avatar.png'],
        ]);

        $sut = $this->getSamlProvider($mapping, $user);
        $tokenUser = $sut->findUser($token);

        self::assertNull($tokenUser->getAvatar());
    }

    public function testFindUserKeepsValidAvatar(): void
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$avatar', 'kimai' => 'avatar'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $user = new User();
        $user->setAuth(User::AUTH_SAML);
        $user->setUserIdentifier('foo1@example.com');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'avatar' => ['https://www.example.com/avatar.png'],
        ]);

        $sut = $this->getSamlProvider($mapping, $user);
        $tokenUser = $sut->findUser($token);

        self::assertEquals('https://www.example.com/avatar.png', $tokenUser->getAvatar());
    }

    public function testAuthenticateThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed creating or hydrating user "foo1@example.com": Missing SAML attribute in response: Email');

        $user = new User();
        $user->setAuth(User::AUTH_SAML);
        $user->setUserIdentifier('foo1@example.com');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'Chicken' => ['foo@example.com'],
        ]);

        $sut = $this->getSamlProvider(null, $user);
        $sut->findUser($token);
    }

    public function testAuthenticateNotThrowsOnOptionalAttribute(): void
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$Chicken', 'kimai' => 'alias'],
                ['saml' => '$$Email', 'kimai' => 'title'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $user = new User();
        $user->setAuth(User::AUTH_SAML);
        $user->setUserIdentifier('foo1@example.com');
        $user->setTitle('I will not be overwritten');

        $token = new SamlLoginAttributes();
        $token->setUserIdentifier($user->getUserIdentifier());
        $token->setAttributes([
            'Chicken' => ['foo@example.com'],
        ]);

        $sut = $this->getSamlProvider($mapping, $user);
        $tokenUser = $sut->findUser($token);
        self::assertSame($user, $tokenUser);
        self::assertTrue($tokenUser->isSamlUser());
        self::assertEquals('foo@example.com', $tokenUser->getAlias());
        self::assertEquals('I will not be overwritten', $tokenUser->getTitle());
    }
}

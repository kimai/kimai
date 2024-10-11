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
use App\User\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @covers \App\Saml\SamlProvider
 */
class SamlProviderTest extends TestCase
{
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

        $provider = new SamlProvider($userService, $userProvider, $samlConfig, $this->createMock(LoggerInterface::class));

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
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Provider;

use App\Configuration\SamlConfiguration;
use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Saml\Provider\SamlProvider;
use App\Saml\SamlTokenFactory;
use App\Saml\Token\SamlToken;
use App\Saml\User\SamlUserFactory;
use App\Security\DoctrineUserProvider;
use App\Tests\Configuration\TestConfigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\ChainUserProvider;

/**
 * @covers \App\Saml\Provider\SamlProvider
 */
class SamlProviderTest extends TestCase
{
    protected function getSamlProvider(array $mapping = null, ?User $user = null, ?SamlUserFactory $userFactory = null): SamlProvider
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

        if (null === $userFactory) {
            $configuration = new SystemConfiguration(new TestConfigLoader([]), [
                'saml' => $mapping
            ]);

            $userFactory = new SamlUserFactory(new SamlConfiguration($configuration));
        }

        $systemConfig = new SystemConfiguration(new TestConfigLoader([]), ['saml' => ['activate' => true]]);
        $samlConfig = new SamlConfiguration($systemConfig);

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        if ($user !== null) {
            $repository->expects($this->once())->method('loadUserByUsername')->willReturn($user);
        }
        $userProvider = new ChainUserProvider([new DoctrineUserProvider($repository)]);
        $provider = new SamlProvider($repository, $userProvider, new SamlTokenFactory(), $userFactory, $samlConfig);

        return $provider;
    }

    public function testSupportsToken()
    {
        $sut = $this->getSamlProvider();
        self::assertFalse($sut->supports(new AnonymousToken('ads', 'ads')));
        self::assertFalse($sut->supports(new UsernamePasswordToken('ads', 'ads', 'asd')));
        self::assertTrue($sut->supports(new SamlToken([])));
    }

    public function testAuthenticateHydratesUser()
    {
        $user = new User();
        $user->setAuth(User::AUTH_SAML);

        $token = new SamlToken([]);
        $token->setUser('foo1@example.com');
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'title' => ['Tralalala'],
        ]);
        self::assertFalse($token->isAuthenticated());

        $sut = $this->getSamlProvider(null, $user);
        $authToken = $sut->authenticate($token);

        self::assertTrue($authToken->isAuthenticated());

        /** @var User $tokenUser */
        $tokenUser = $authToken->getUser();

        self::assertSame($user, $tokenUser);
        self::assertEquals('foo1@example.com', $tokenUser->getUsername());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testAuthenticatCreatesNewUser()
    {
        $token = new SamlToken([]);
        $token->setUser('foo1@example.com');
        $token->setAttributes([
            'Email' => ['foo@example.com'],
            'title' => ['Tralalala'],
        ]);
        self::assertFalse($token->isAuthenticated());

        $sut = $this->getSamlProvider(null);
        $authToken = $sut->authenticate($token);

        self::assertTrue($authToken->isAuthenticated());

        /** @var User $tokenUser */
        $tokenUser = $authToken->getUser();

        self::assertEquals('foo1@example.com', $tokenUser->getUsername());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testAuthenticateThrowsAuthenticationException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed creating or hydrating user "foo1@example.com": Missing user attribute: Email');

        $user = new User();
        $user->setAuth(User::AUTH_SAML);

        $token = new SamlToken([]);
        $token->setUser('foo1@example.com');
        $token->setAttributes([
            'Chicken' => ['foo@example.com'],
        ]);

        $sut = $this->getSamlProvider(null, $user);
        $sut->authenticate($token);
    }
}

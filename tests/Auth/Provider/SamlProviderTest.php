<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Auth\Provider;

use App\Auth\Provider\SamlProvider;
use App\Auth\User\SamlUserFactory;
use App\Auth\User\SamlUserProvider;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @covers \App\Auth\Provider\SamlProvider
 */
class SamlProviderTest extends TestCase
{
    protected function getSamlProvider($mapping = null, $loadUser = false): SamlProvider
    {
        if (null === $mapping) {
            $mapping = [
                'mapping' => [
                    ['saml' => '$$avatar', 'kimai' => 'avatar'],
                    ['saml' => '$Email', 'kimai' => 'email'],
                    ['saml' => '$title', 'kimai' => 'title'],
                ],
                'roles' => [
                    'attribute' => '',
                    'mapping' => []
                ]
            ];
        }

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        if ($loadUser !== false) {
            $repository->expects($this->once())->method('loadUserByUsername')->willReturn($loadUser);
        }
        $userProvider = new SamlUserProvider($repository);
        $provider = new SamlProvider($userProvider, []);

        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $provider->setEntityManager($em);

        $provider->setTokenFactory(new SamlTokenFactory());
        $provider->setUserFactory(new SamlUserFactory($mapping));

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
        self::assertEquals('foo@example.com', $tokenUser->getUsername());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }

    public function testAuthenticatCreatesNewUser()
    {
        $token = new SamlToken([]);
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

        self::assertEquals('foo@example.com', $tokenUser->getUsername());
        self::assertEquals('Tralalala', $tokenUser->getTitle());
        self::assertEquals('foo@example.com', $tokenUser->getEmail());
    }
}

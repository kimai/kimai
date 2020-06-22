<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Saml\Provider\SamlProvider;
use App\Saml\SamlTokenFactory;
use App\Saml\User\SamlUserFactory;
use App\Security\DoctrineUserProvider;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\ChainUserProvider;

/**
 * @covers \App\Saml\Provider\SamlProvider
 */
class SamlProviderTest extends TestCase
{
    protected function getSamlProvider($mapping = null, $loadUser = false): SamlProvider
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

        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        if ($loadUser !== false) {
            $repository->expects($this->once())->method('loadUserByUsername')->willReturn($loadUser);
        }
        $userProvider = new ChainUserProvider([new DoctrineUserProvider($repository)]);
        $provider = new SamlProvider($repository, $userProvider, new SamlTokenFactory(), new SamlUserFactory($mapping));

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
}

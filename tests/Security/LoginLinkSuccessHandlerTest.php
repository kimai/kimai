<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\LoginLinkSuccessHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\HttpUtils;

#[CoversClass(LoginLinkSuccessHandler::class)]
class LoginLinkSuccessHandlerTest extends TestCase
{
    private const LINK_HASH = 'the-hash-of-the-reset-link';
    private const LINK_URL = '/en/auth/link/check?user=foo&expires=1234&hash=' . self::LINK_HASH;

    private function createSut(UserRepository $repository): LoginLinkSuccessHandler
    {
        $sut = new LoginLinkSuccessHandler(new HttpUtils(), $repository);
        // called by Symfony when the handler is configured, see CustomAuthenticationSuccessHandler
        $sut->setFirewallName('secured_area');
        $sut->setOptions(['default_target_path' => '/homepage']);

        return $sut;
    }

    private function createRequest(?string $hash = self::LINK_HASH): Request
    {
        $request = Request::create('/', 'GET', $hash !== null ? ['hash' => $hash] : []);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUserIdentifier('foo');
        $user->setEnabled(true);

        return $user;
    }

    private function createToken(UserInterface $user): PostAuthenticationToken
    {
        return new PostAuthenticationToken($user, 'secured_area', $user->getRoles());
    }

    private function assertRedirectToTargetPath(?Response $response): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('http://localhost/homepage', $response->getTargetUrl());
    }

    public function testPasswordResetLinkRequiresPasswordReset(): void
    {
        $user = $this->createUser();
        $user->markPasswordResetLink(self::LINK_URL);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('saveUser')->with($user);

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), $this->createToken($user));

        self::assertTrue($user->requiresPasswordReset());
        // the link is used up
        self::assertFalse($user->isPasswordResetLink(self::LINK_HASH));
        $this->assertRedirectToTargetPath($response);
    }

    /**
     * A pending password reset must not affect other login links, e.g. one created by an administrator.
     */
    public function testOtherLoginLinkIsNormalLogin(): void
    {
        $user = $this->createUser();
        $user->markPasswordRequested();
        $user->markPasswordResetLink(self::LINK_URL);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest('another-link'), $this->createToken($user));

        self::assertFalse($user->requiresPasswordReset());
        self::assertTrue($user->isPasswordResetLink(self::LINK_HASH));
        $this->assertRedirectToTargetPath($response);
    }

    public function testRequestWithoutHashIsNormalLogin(): void
    {
        $user = $this->createUser();
        $user->markPasswordResetLink(self::LINK_URL);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(null), $this->createToken($user));

        self::assertFalse($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testLoginLinkWithoutPasswordResetLink(): void
    {
        $user = $this->createUser();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), $this->createToken($user));

        self::assertFalse($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testUnknownUserClass(): void
    {
        $user = new InMemoryUser('foo', null);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), $this->createToken($user));

        $this->assertRedirectToTargetPath($response);
    }

    public function testTargetPathFromRequest(): void
    {
        $user = $this->createUser();

        $repository = $this->createMock(UserRepository::class);

        $request = $this->createRequest();
        $request->query->set('_target_path', '/en/timesheet/');

        $response = $this->createSut($repository)->onAuthenticationSuccess($request, $this->createToken($user));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('http://localhost/en/timesheet/', $response->getTargetUrl());
    }
}

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
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\HttpUtils;

#[CoversClass(LoginLinkSuccessHandler::class)]
class LoginLinkSuccessHandlerTest extends TestCase
{
    private function createSut(UserRepository $repository): LoginLinkSuccessHandler
    {
        $configuration = SystemConfigurationFactory::createStub(['user' => ['password_reset_retry_ttl' => 3600]]);

        $sut = new LoginLinkSuccessHandler(new HttpUtils(), $configuration, $repository);
        $sut->setFirewallName('secured_area');
        $sut->setOptions(['default_target_path' => '/homepage']);

        return $sut;
    }

    private function createRequest(): Request
    {
        $request = Request::create('/');
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

    private function assertRedirectToTargetPath(?Response $response): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('http://localhost/homepage', $response->getTargetUrl());
    }

    public function testRequestedPasswordResetIsRequired(): void
    {
        $user = $this->createUser();
        $user->markPasswordRequested();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('saveUser')->with($user);

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        self::assertTrue($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testLoginLinkWithoutPasswordResetRequest(): void
    {
        $user = $this->createUser();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        self::assertFalse($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testExpiredPasswordResetRequest(): void
    {
        $user = $this->createUser();
        $reflection = new \ReflectionProperty(User::class, 'passwordRequestedAt');
        $reflection->setValue($user, new \DateTimeImmutable('-2 hours'));

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        self::assertFalse($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testAlreadyRequiredPasswordResetIsNotSavedAgain(): void
    {
        $user = $this->createUser();
        $user->markPasswordRequested();
        $user->setRequiresPasswordReset(true);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        self::assertTrue($user->requiresPasswordReset());
        $this->assertRedirectToTargetPath($response);
    }

    public function testUnknownUserClass(): void
    {
        $user = new InMemoryUser('foo', null);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveUser');

        $response = $this->createSut($repository)->onAuthenticationSuccess($this->createRequest(), new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        $this->assertRedirectToTargetPath($response);
    }

    public function testTargetPathFromRequest(): void
    {
        $user = $this->createUser();

        $repository = $this->createMock(UserRepository::class);

        $request = $this->createRequest();
        $request->query->set('_target_path', '/en/timesheet/');

        $response = $this->createSut($repository)->onAuthenticationSuccess($request, new PostAuthenticationToken($user, 'secured_area', $user->getRoles()));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('http://localhost/en/timesheet/', $response->getTargetUrl());
    }
}

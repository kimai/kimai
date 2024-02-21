<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Authentication;

use App\API\Authentication\TokenAuthenticator;
use App\Entity\User;
use App\Repository\ApiUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @covers \App\API\Authentication\TokenAuthenticator
 * @group legacy
 */
class TokenAuthenticatorTest extends TestCase
{
    private function getSut(bool $verify = true): TokenAuthenticator
    {
        $userProvider = $this->createMock(ApiUserRepository::class);
        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('verify')->willReturn($verify);
        $passwordHasherFactory->method('getPasswordHasher')->willReturn($passwordHasher);

        return new TokenAuthenticator($userProvider, $passwordHasherFactory);
    }

    public function testSupports(): void
    {
        $sut = $this->getSut();

        // not supporting because /api path is not the beginning of the URL
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'dfghj/api/doc/dfghj']);
        self::assertFalse($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo']);
        self::assertFalse($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/doc']);
        self::assertFalse($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        self::assertTrue($sut->supports($request));
    }

    public function testAuthenticateWithMissingAuthHeader(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing user header: X-AUTH-USER');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo']);
        $sut->authenticate($request);
    }

    public function testAuthenticateWithMissingToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing token header: X-AUTH-TOKEN');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo']);
        $sut->authenticate($request);
    }

    public function testAuthenticateWithEmptyToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing token header: X-AUTH-TOKEN');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo', 'HTTP_X-AUTH-TOKEN' => '']);
        $sut->authenticate($request);
    }

    public function testAuthenticateWithMissingUser(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing user header: X-AUTH-USER');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $sut->authenticate($request);
    }

    public function testAuthenticateWithEmptyUser(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing user header: X-AUTH-USER');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => '', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $sut->authenticate($request);
    }

    public function testAuthenticate(): void
    {
        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo2', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $passport = $sut->authenticate($request);
        self::assertInstanceOf(Passport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertEquals('foo2', $badge->getUserIdentifier());

        $user = new User();
        $user->setApiToken('bar2');

        $badge = $passport->getBadge(CustomCredentials::class);
        self::assertInstanceOf(CustomCredentials::class, $badge);
        self::assertFalse($badge->isResolved());
        $badge->executeCustomChecker($user);
        self::assertTrue($badge->isResolved());
    }

    public function testAuthenticateFailsOnMissingApiTokenForUser(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The user has no activated API account.');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo2', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $passport = $sut->authenticate($request);

        $user = new User();

        /** @var CustomCredentials $badge */
        $badge = $passport->getBadge(CustomCredentials::class);
        $badge->executeCustomChecker($user);
    }

    public function testAuthenticateFailsOnWrongPassword(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The presented password is invalid.');

        $sut = $this->getSut(false);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo2', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $passport = $sut->authenticate($request);

        $user = new User();
        $user->setApiToken('bar');

        /** @var CustomCredentials $badge */
        $badge = $passport->getBadge(CustomCredentials::class);
        $badge->executeCustomChecker($user);
    }
}

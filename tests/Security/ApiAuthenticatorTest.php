<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Security\ApiAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @covers \App\Security\ApiAuthenticator
 */
class ApiAuthenticatorTest extends TestCase
{
    public function testSupports()
    {
        $sut = new ApiAuthenticator();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'dfghj/api/doc/dfghj']);
        self::assertFalse($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo']);
        self::assertTrue($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-SESSION' => true]);
        self::assertFalse($sut->supports($request));
    }

    public function testAuthenticateWithMissingToken()
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing token header: X-AUTH-TOKEN');

        $sut = new ApiAuthenticator();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo']);
        $passport = $sut->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertEquals('foo', $badge->getUserIdentifier());
    }

    public function FIXME_testAuthenticateWithSomethingWrong()
    {
        $sut = new ApiAuthenticator();

        /*
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-SESSION' => true]);

        $passport = $sut->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertEquals('', $badge->getUserIdentifier());
        */
    }

    public function testAuthenticateWithMissingUser()
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required, missing user header: X-AUTH-USER');

        $sut = new ApiAuthenticator();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $passport = $sut->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertEquals('foo2', $badge->getUserIdentifier());
    }

    public function testAuthenticate()
    {
        $sut = new ApiAuthenticator();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo2', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $passport = $sut->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertEquals('foo2', $badge->getUserIdentifier());
    }
}

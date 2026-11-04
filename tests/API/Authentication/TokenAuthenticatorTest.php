<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Authentication;

use App\API\Authentication\TokenAuthenticator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

#[CoversClass(TokenAuthenticator::class)]
#[Group('legacy')]
class TokenAuthenticatorTest extends TestCase
{
    private function getSut(): TokenAuthenticator
    {
        return new TokenAuthenticator(
            new RateLimiterFactory(['id' => 'foo', 'policy' => 'fixed_window', 'limit' => 10, 'interval' => '1 minute'], new InMemoryStorage()),
        );
    }

    public function testSupports(): void
    {
        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo']);
        self::assertFalse($sut->supports($request));

        // deprecated API passwords are rejected
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        self::assertFalse($sut->supports($request));

        // still rejected after the rate limit (which prevents log spam) is exceeded
        for ($i = 0; $i < 15; $i++) {
            self::assertFalse($sut->supports($request));
        }
    }

    public function testAuthenticateIsRejected(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Rejected deprecated API password. Upgrade your APP to use API tokens instead.');

        $sut = $this->getSut();

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        $sut->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $sut = $this->getSut();

        self::assertNull($sut->onAuthenticationSuccess(new Request(), $this->createMock(TokenInterface::class), 'api'));
    }

    public function testOnAuthenticationFailure(): void
    {
        $sut = $this->getSut();

        $response = $sut->onAuthenticationFailure(new Request(), new BadCredentialsException('foo'));
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertEquals('{"message":"Invalid credentials"}', $response->getContent());

        $response = $sut->onAuthenticationFailure(new Request(), new CustomUserMessageAuthenticationException('bar'));
        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertEquals('{"message":"bar"}', $response->getContent());
    }
}

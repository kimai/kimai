<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Oidc;

use App\Configuration\OidcConfigurationInterface;
use App\Entity\User;
use App\Oidc\OidcAuthenticator;
use App\Oidc\OidcBadge;
use App\Oidc\OidcClient;
use App\Oidc\OidcLoginAttributes;
use App\Oidc\OidcProvider;
use App\Oidc\OidcToken;
use App\Oidc\Security\OidcAuthenticationFailureHandler;
use App\Oidc\Security\OidcAuthenticationSuccessHandler;
use App\User\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\HttpUtils;

#[CoversClass(OidcAuthenticator::class)]
class OidcAuthenticatorTest extends TestCase
{
    public function testSupportsReturnsFalseIfOidcIsDisabled(): void
    {
        $sut = $this->createSut($this->createConfiguration(false));

        self::assertFalse($sut->supports($this->createRequest(['code' => 'abc'])));
    }

    public function testSupportsReturnsFalseForNonGetRequests(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        $request = Request::create('/oidc_callback', 'POST', ['code' => 'abc']);

        self::assertFalse($sut->supports($request));
    }

    public function testSupportsReturnsFalseForDifferentPath(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertFalse($sut->supports(Request::create('/login', 'GET', ['code' => 'abc'])));
    }

    public function testSupportsReturnsFalseWithoutCodeOrError(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertFalse($sut->supports($this->createRequest()));
    }

    public function testSupportsReturnsTrueWithCode(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertTrue($sut->supports($this->createRequest(['code' => 'abc'])));
    }

    public function testSupportsReturnsTrueWithError(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertTrue($sut->supports($this->createRequest(['error' => 'access_denied'])));
    }

    public function testAuthenticateBuildsPassport(): void
    {
        $oidcClient = $this->createMock(OidcClient::class);
        $oidcClient
            ->expects($this->once())
            ->method('fetchUserClaims')
            ->with('auth-code', $this->anything(), 'nonce-abc', 'verifier-abc')
            ->willReturn([
                'preferred_username' => 'john@example.com',
                'email' => 'john@example.com',
            ]);

        $sut = $this->createSut($this->createConfiguration(), $oidcClient);

        $request = $this->createRequest(
            ['code' => 'auth-code', 'state' => 'state-abc'],
            [
                OidcAuthenticator::SESSION_STATE => 'state-abc',
                OidcAuthenticator::SESSION_NONCE => 'nonce-abc',
                OidcAuthenticator::SESSION_PKCE => 'verifier-abc',
            ]
        );

        $passport = $sut->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);

        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);
        self::assertSame('john@example.com', $userBadge->getUserIdentifier());

        self::assertTrue($passport->hasBadge(RememberMeBadge::class));

        $oidcBadge = $passport->getBadge(OidcBadge::class);
        self::assertInstanceOf(OidcBadge::class, $oidcBadge);
        self::assertSame('john@example.com', $oidcBadge->getOidcLoginAttributes()->getUserIdentifier());
    }

    public function testAuthenticateResolvesArrayIdentifier(): void
    {
        $oidcClient = $this->createMock(OidcClient::class);
        $oidcClient
            ->expects($this->once())
            ->method('fetchUserClaims')
            ->willReturn([
                'preferred_username' => ['john@example.com', 'second@example.com'],
            ]);

        $sut = $this->createSut($this->createConfiguration(), $oidcClient);

        $passport = $sut->authenticate($this->createValidRequest());

        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);
        self::assertSame('john@example.com', $userBadge->getUserIdentifier());
    }

    public function testAuthenticateThrowsOnProviderError(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC login failed: access_denied');

        $sut = $this->createSut($this->createConfiguration());

        $sut->authenticate($this->createRequest(['error' => 'access_denied']));
    }

    public function testAuthenticateThrowsOnStateMismatch(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC state mismatch.');

        $sut = $this->createSut($this->createConfiguration());

        $request = $this->createRequest(
            ['code' => 'auth-code', 'state' => 'wrong-state'],
            [OidcAuthenticator::SESSION_STATE => 'state-abc']
        );

        $sut->authenticate($request);
    }

    public function testAuthenticateThrowsOnMissingState(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC state mismatch.');

        $sut = $this->createSut($this->createConfiguration());

        $request = $this->createRequest(['code' => 'auth-code', 'state' => 'state-abc']);

        $sut->authenticate($request);
    }

    public function testAuthenticateThrowsOnMissingNonce(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC nonce is missing.');

        $sut = $this->createSut($this->createConfiguration());

        $request = $this->createRequest(
            ['code' => 'auth-code', 'state' => 'state-abc'],
            [OidcAuthenticator::SESSION_STATE => 'state-abc']
        );

        $sut->authenticate($request);
    }

    public function testAuthenticateThrowsOnMissingCodeVerifier(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('OIDC PKCE code verifier is missing.');

        $sut = $this->createSut($this->createConfiguration());

        $request = $this->createRequest(
            ['code' => 'auth-code', 'state' => 'state-abc'],
            [
                OidcAuthenticator::SESSION_STATE => 'state-abc',
                OidcAuthenticator::SESSION_NONCE => 'nonce-abc',
            ]
        );

        $sut->authenticate($request);
    }

    public function testAuthenticateThrowsWhenUsernameClaimIsMissing(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Claim "preferred_username" not found in OIDC response.');

        $oidcClient = $this->createMock(OidcClient::class);
        $oidcClient->method('fetchUserClaims')->willReturn(['email' => 'john@example.com']);

        $sut = $this->createSut($this->createConfiguration(), $oidcClient);

        $sut->authenticate($this->createValidRequest());
    }

    public function testAuthenticateThrowsWhenIdentifierIsEmpty(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Claim "preferred_username" did not contain a valid user identifier.');

        $oidcClient = $this->createMock(OidcClient::class);
        $oidcClient->method('fetchUserClaims')->willReturn(['preferred_username' => '']);

        $sut = $this->createSut($this->createConfiguration(), $oidcClient);

        $sut->authenticate($this->createValidRequest());
    }

    public function testCreateTokenCopiesUserRolesAndOidcAttributes(): void
    {
        $user = new User();
        $user->setUserIdentifier('oidc-user');
        $user->setRoles(['ROLE_TEAMLEAD']);

        $loginAttributes = new OidcLoginAttributes();
        $loginAttributes->setAttributes([
            'email' => 'foo@example.com',
            'name' => 'John Doe',
        ]);

        $passport = new SelfValidatingPassport(
            new UserBadge('oidc-user', static fn (): User => $user),
            [new OidcBadge($loginAttributes)]
        );

        $sut = $this->createSut($this->createConfiguration());
        $token = $sut->createToken($passport, 'secured_area');

        self::assertInstanceOf(OidcToken::class, $token);
        self::assertSame($user, $token->getUser());
        self::assertSame('oidc-user', $token->getUserIdentifier());
        self::assertEqualsCanonicalizing(['ROLE_TEAMLEAD', 'ROLE_USER'], $token->getRoleNames());
        self::assertSame([
            'email' => 'foo@example.com',
            'name' => 'John Doe',
        ], $token->getAttributes());
    }

    public function testAuthenticationSuccessDelegatesToSuccessHandler(): void
    {
        $user = new User();
        $user->setUserIdentifier('success-user');
        $token = $this->createTokenForUser($user);
        $successHandler = $this->createSuccessHandler(['homepage' => '/dashboard']);

        $sut = $this->createSut($this->createConfiguration(), successHandler: $successHandler);

        $response = $sut->onAuthenticationSuccess(Request::create('/oidc_callback', 'GET'), $token, 'secured_area');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/dashboard', $response->getTargetUrl());
    }

    public function testAuthenticationFailureDelegatesToFailureHandler(): void
    {
        $request = Request::create('/oidc_callback', 'GET');
        $request->attributes->set('_stateless', true);
        $failureHandler = $this->createFailureHandler(['login' => '/login']);

        $sut = $this->createSut($this->createConfiguration(), failureHandler: $failureHandler);

        $response = $sut->onAuthenticationFailure($request, new AuthenticationException('failure'));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
    }

    private function createSut(
        OidcConfigurationInterface $configuration,
        ?OidcClient $oidcClient = null,
        ?OidcProvider $provider = null,
        ?OidcAuthenticationSuccessHandler $successHandler = null,
        ?OidcAuthenticationFailureHandler $failureHandler = null,
        ?LoggerInterface $logger = null
    ): OidcAuthenticator {
        return new OidcAuthenticator(
            new HttpUtils($this->createUrlGenerator(), $this->createUrlMatcher()),
            $successHandler ?? $this->createSuccessHandler(),
            $failureHandler ?? $this->createFailureHandler(),
            $oidcClient ?? $this->createMock(OidcClient::class),
            $provider ?? $this->createUnusedProvider(),
            $configuration,
            $this->createUrlGenerator(),
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @return OidcConfigurationInterface&MockObject
     */
    private function createConfiguration(bool $activated = true, string $usernameClaim = 'preferred_username'): OidcConfigurationInterface
    {
        $configuration = $this->createMock(OidcConfigurationInterface::class);
        $configuration->method('isActivated')->willReturn($activated);
        $configuration->method('getUsernameClaim')->willReturn($usernameClaim);

        return $configuration;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $session
     */
    private function createRequest(array $query = [], array $session = []): Request
    {
        $request = Request::create('/oidc_callback', 'GET', $query);

        $store = new Session(new MockArraySessionStorage());
        foreach ($session as $key => $value) {
            $store->set($key, $value);
        }
        $request->setSession($store);

        return $request;
    }

    private function createValidRequest(): Request
    {
        return $this->createRequest(
            ['code' => 'auth-code', 'state' => 'state-abc'],
            [
                OidcAuthenticator::SESSION_STATE => 'state-abc',
                OidcAuthenticator::SESSION_NONCE => 'nonce-abc',
                OidcAuthenticator::SESSION_PKCE => 'verifier-abc',
            ]
        );
    }

    private function createTokenForUser(User $user): TokenInterface
    {
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn (): User => $user));

        return $this->createSut($this->createConfiguration())->createToken($passport, 'secured_area');
    }

    private function createUnusedProvider(): OidcProvider
    {
        /** @var UserProviderInterface<User>&MockObject $userProvider */
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['refreshUser', 'supportsClass', 'loadUserByIdentifier'])
            ->getMock();
        $userProvider->expects($this->never())->method('loadUserByIdentifier');

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->never())->method('createNewUser');
        $userService->expects($this->never())->method('saveUser');

        return new OidcProvider(
            $userService,
            $userProvider,
            $this->createMock(OidcConfigurationInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function createSuccessHandler(array $rules = []): OidcAuthenticationSuccessHandler
    {
        return new OidcAuthenticationSuccessHandler(new HttpUtils($this->createUrlGenerator($rules)));
    }

    private function createFailureHandler(array $rules = []): OidcAuthenticationFailureHandler
    {
        return new OidcAuthenticationFailureHandler(
            $this->createMock(HttpKernelInterface::class),
            new HttpUtils($this->createUrlGenerator($rules))
        );
    }

    /**
     * @param array<string, string> $rules
     */
    private function createUrlGenerator(array $rules = []): UrlGeneratorInterface
    {
        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $urlGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturnCallback(static function ($name) use ($rules): string {
                if (\array_key_exists((string) $name, $rules)) {
                    return $rules[(string) $name];
                }

                return (string) $name;
            })
        ;

        return $urlGenerator;
    }

    /**
     * @return UrlMatcherInterface&MockObject
     */
    private function createUrlMatcher(): UrlMatcherInterface
    {
        $matcher = $this->getMockBuilder(UrlMatcherInterface::class)->getMock();
        $matcher
            ->expects($this->any())
            ->method('match')
            ->willReturnCallback(static function (string $pathInfo): array {
                return ['_route' => ltrim($pathInfo, '/')];
            })
        ;

        return $matcher;
    }
}

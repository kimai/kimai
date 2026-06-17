<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml;

use App\Configuration\SamlConfigurationInterface;
use App\Entity\User;
use App\Saml\SamlAuthenticator;
use App\Saml\SamlAuthFactory;
use App\Saml\SamlBadge;
use App\Saml\SamlLoginAttributes;
use App\Saml\SamlProvider;
use App\Saml\Security\SamlAuthenticationFailureHandler;
use App\Saml\Security\SamlAuthenticationSuccessHandler;
use App\User\UserService;
use OneLogin\Saml2\Auth;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

final class TestSamlConfiguration implements SamlConfigurationInterface
{
    public function __construct(
        private readonly bool $activated = true,
        private readonly ?string $usernameAttribute = null,
    ) {
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function getTitle(): string
    {
        return 'SAML';
    }

    public function getProvider(): string
    {
        return 'provider';
    }

    public function getAttributeMapping(): array
    {
        return [];
    }

    public function getRolesAttribute(): ?string
    {
        return null;
    }

    public function getRolesMapping(): array
    {
        return [];
    }

    public function isRolesResetOnLogin(): bool
    {
        return false;
    }

    public function getConnection(): array
    {
        return [];
    }

    public function getUsernameAttribute(): ?string
    {
        return $this->usernameAttribute;
    }
}

#[CoversClass(SamlAuthenticator::class)]
class SamlAuthenticatorTest extends TestCase
{
    public function testSupportsReturnsFalseIfSamlIsDisabled(): void
    {
        $sut = $this->createSut($this->createConfiguration(false));

        self::assertFalse($sut->supports(Request::create('/saml_acs', 'POST')));
    }

    public function testSupportsReturnsFalseForNonPostRequests(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertFalse($sut->supports(Request::create('/saml_acs', 'GET')));
    }

    public function testSupportsReturnsFalseForDifferentPath(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertFalse($sut->supports(Request::create('/login', 'POST')));
    }

    public function testSupportsReturnsTrueForPostRequestOnCheckPath(): void
    {
        $sut = $this->createSut($this->createConfiguration());

        self::assertTrue($sut->supports(Request::create('/saml_acs', 'POST')));
    }

    public function testAuthenticateUsesNameIdAndBuildsPassport(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->expects($this->once())->method('processResponse');
        $auth->expects($this->once())->method('getErrors')->willReturn([]);
        $auth->expects($this->once())->method('getAttributes')->willReturn([
            'mail' => ['foo@example.com'],
        ]);
        $auth->expects($this->once())->method('getSessionIndex')->willReturn('session-123');
        $auth->expects($this->once())->method('getNameId')->willReturn('name-id@example.com');
        $auth->expects($this->never())->method('getAttributesWithFriendlyName');

        $expectedUser = new User();
        $expectedUser->setUserIdentifier('name-id@example.com');

        $configuration = $this->createConfiguration();
        $provider = $this->createProviderForExistingUser($configuration, $expectedUser);

        $sut = $this->createSut(
            $configuration,
            $provider,
            authFactory: $this->createAuthFactory($auth)
        );

        $passport = $sut->authenticate(Request::create('/saml_acs', 'POST'));

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertSame($expectedUser, $passport->getUser());

        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);
        self::assertSame('name-id@example.com', $userBadge->getUserIdentifier());

        self::assertTrue($passport->hasBadge(RememberMeBadge::class));

        $samlBadge = $passport->getBadge(SamlBadge::class);
        self::assertInstanceOf(SamlBadge::class, $samlBadge);
        self::assertSame([
            'mail' => ['foo@example.com'],
            'sessionIndex' => 'session-123',
        ], $samlBadge->getSamlLoginAttributes()->getAttributes());
    }

    public function testAuthenticateUsesFriendlyAttributeNamesAndConfiguredUsernameAttribute(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->expects($this->once())->method('processResponse');
        $auth->expects($this->once())->method('getErrors')->willReturn([]);
        $auth->expects($this->never())->method('getAttributes');
        $auth->expects($this->once())->method('getAttributesWithFriendlyName')->willReturn([
            'uid' => ['friendly-user'],
            'mail' => ['friendly@example.com'],
        ]);
        $auth->expects($this->once())->method('getSessionIndex')->willReturn('friendly-session');
        $auth->expects($this->never())->method('getNameId');

        $configuration = $this->createConfiguration(usernameAttribute: 'uid');
        $user = new User();
        $user->setUserIdentifier('friendly-user');
        $provider = $this->createProviderForExistingUser($configuration, $user);

        $sut = $this->createSut($configuration, $provider, authFactory: $this->createAuthFactory($auth));
        $this->setUseAttributeFriendlyName($sut, true);

        $passport = $sut->authenticate(Request::create('/saml_acs', 'POST'));

        self::assertSame('friendly-user', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateThrowsIfSamlResponseContainsErrors(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->expects($this->once())->method('processResponse');
        $auth->expects($this->once())->method('getErrors')->willReturn(['invalid_response']);
        $auth->expects($this->exactly(2))->method('getLastErrorReason')->willReturn('Signature validation failed');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('SAML login failed: Signature validation failed');

        $sut = $this->createSut(
            $this->createConfiguration(),
            authFactory: $this->createAuthFactory($auth),
            logger: $logger
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Signature validation failed');

        $sut->authenticate(Request::create('/saml_acs', 'POST'));
    }

    public function testAuthenticateThrowsIfConfiguredUsernameAttributeIsMissing(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->expects($this->once())->method('processResponse');
        $auth->expects($this->once())->method('getErrors')->willReturn([]);
        $auth->expects($this->once())->method('getAttributes')->willReturn([
            'mail' => ['foo@example.com'],
        ]);
        $auth->expects($this->once())->method('getSessionIndex')->willReturn('missing-session');
        $auth->expects($this->never())->method('getNameId');

        $configuration = $this->createConfiguration(usernameAttribute: 'uid');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with("Attribute 'uid' not found in SAML data");

        $sut = $this->createSut(
            $configuration,
            authFactory: $this->createAuthFactory($auth),
            logger: $logger
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Attribute 'uid' not found in SAML data");

        $sut->authenticate(Request::create('/saml_acs', 'POST'));
    }

    public function testCreateTokenCopiesUserRolesAndSamlAttributes(): void
    {
        $user = new User();
        $user->setUserIdentifier('saml-user');
        $user->setRoles(['ROLE_USER', 'ROLE_TEAMLEAD']);

        $loginAttributes = new SamlLoginAttributes();
        $loginAttributes->setAttributes([
            'mail' => ['foo@example.com'],
            'sessionIndex' => 'token-session',
        ]);

        $passport = new SelfValidatingPassport(
            new UserBadge('saml-user', static fn (): User => $user),
            [new SamlBadge($loginAttributes)]
        );

        $sut = $this->createSut($this->createConfiguration());
        $token = $sut->createToken($passport, 'secured_area');

        self::assertSame($user, $token->getUser());
        self::assertSame('saml-user', $token->getUserIdentifier());
        self::assertEqualsCanonicalizing(['ROLE_USER', 'ROLE_TEAMLEAD'], $token->getRoleNames());
        self::assertSame([
            'mail' => ['foo@example.com'],
            'sessionIndex' => 'token-session',
        ], $token->getAttributes());
    }

    public function testAuthenticationSuccessDelegatesToSuccessHandler(): void
    {
        $user = new User();
        $user->setUserIdentifier('success-user');
        $token = $this->createTokenForUser($user);
        $request = Request::create('/saml_acs', 'POST');
        $successHandler = $this->createSuccessHandler(['homepage' => '/dashboard']);

        $sut = $this->createSut($this->createConfiguration(), successHandler: $successHandler);

        $response = $sut->onAuthenticationSuccess($request, $token, 'secured_area');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/dashboard', $response->getTargetUrl());
    }

    public function testAuthenticationFailureDelegatesToFailureHandler(): void
    {
        $request = Request::create('/saml_acs', 'POST');
        $request->attributes->set('_stateless', true);
        $exception = new AuthenticationException('failure');
        $failureHandler = $this->createFailureHandler(['login' => '/login']);

        $sut = $this->createSut($this->createConfiguration(), failureHandler: $failureHandler);
        $response = $sut->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
    }

    private function createSut(
        TestSamlConfiguration $configuration,
        ?SamlProvider $provider = null,
        ?SamlAuthenticationSuccessHandler $successHandler = null,
        ?SamlAuthenticationFailureHandler $failureHandler = null,
        ?SamlAuthFactory $authFactory = null,
        ?LoggerInterface $logger = null
    ): SamlAuthenticator {
        return new SamlAuthenticator(
            new HttpUtils($this->createUrlGenerator(), $this->createUrlMatcher()),
            $successHandler ?? $this->createSuccessHandler(),
            $failureHandler ?? $this->createFailureHandler(),
            $authFactory ?? $this->createMock(SamlAuthFactory::class),
            $provider ?? $this->createUnusedProvider($configuration),
            $configuration,
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }

    private function createConfiguration(bool $activated = true, ?string $usernameAttribute = null): TestSamlConfiguration
    {
        return new TestSamlConfiguration($activated, $usernameAttribute);
    }

    private function createAuthFactory(Auth $auth): SamlAuthFactory
    {
        $factory = $this->createMock(SamlAuthFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($auth);

        return $factory;
    }

    private function createProviderForExistingUser(TestSamlConfiguration $configuration, User $user): SamlProvider
    {
        /** @var UserProviderInterface<User>&MockObject $userProvider */
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['refreshUser', 'supportsClass', 'loadUserByIdentifier'])
            ->getMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with($user->getUserIdentifier())
            ->willReturn($user);

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())->method('saveUser')->with($user);
        $userService->expects($this->never())->method('createNewUser');

        return new SamlProvider($userService, $userProvider, $configuration, $this->createMock(LoggerInterface::class));
    }

    private function createUnusedProvider(TestSamlConfiguration $configuration): SamlProvider
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

        return new SamlProvider($userService, $userProvider, $configuration, $this->createMock(LoggerInterface::class));
    }

    private function createTokenForUser(User $user): TokenInterface
    {
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn () => $user));

        return $this->createSut($this->createConfiguration())->createToken($passport, 'secured_area');
    }

    private function setUseAttributeFriendlyName(SamlAuthenticator $authenticator, bool $value): void
    {
        $property = new \ReflectionProperty($authenticator, 'options');
        $options = $property->getValue($authenticator);
        if (!\is_array($options)) {
            throw new \RuntimeException('Expected authenticator options to be an array');
        }
        $options['use_attribute_friendly_name'] = $value;
        $property->setValue($authenticator, $options);
    }

    private function createSuccessHandler(array $rules = []): SamlAuthenticationSuccessHandler
    {
        return new SamlAuthenticationSuccessHandler(new HttpUtils($this->createUrlGenerator($rules)));
    }

    private function createFailureHandler(array $rules = []): SamlAuthenticationFailureHandler
    {
        return new SamlAuthenticationFailureHandler(
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

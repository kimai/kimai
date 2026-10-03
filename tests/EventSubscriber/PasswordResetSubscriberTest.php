<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\PasswordResetSubscriber;
use App\EventSubscriber\WizardSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[CoversClass(PasswordResetSubscriber::class)]
class PasswordResetSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([KernelEvents::REQUEST => ['onKernelRequest', -20]], PasswordResetSubscriber::getSubscribedEvents());
    }

    public function testPasswordResetHasHigherPriorityThanWizardSubscriber(): void
    {
        self::assertGreaterThan(
            WizardSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1],
            PasswordResetSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1]
        );
    }

    public function testOnKernelRequestIgnoresSubRequest(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->never())->method('getToken');

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard', false);

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresMissingToken(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    /**
     * @return iterable<array{string, string|null}>
     */
    public static function provideExcludedUris(): iterable
    {
        yield ['/api/timesheets', 'get_timesheets'];
        yield ['/api/timesheets?foo=bar', 'get_timesheets'];
        yield ['/register/', 'registration_register'];
        yield ['/register/check-email', 'user_registration_check_email'];
        yield ['/register/confirm/abc', 'registration_confirm'];
        yield ['/register/confirmed', 'registration_confirmed'];
        yield ['/wizard/intro', 'wizard_intro'];
        yield ['/en/wizard/password', 'wizard_password'];
        yield ['/logout', 'logout'];
    }

    #[DataProvider('provideExcludedUris')]
    public function testOnKernelRequestIgnoresExcludedUris(string $uri, ?string $route): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->never())->method('getUser');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent($uri, true, $route);

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($this->createMock(UserInterface::class));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->never())->method('isGranted');

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresUserWithoutFullAuthentication(): void
    {
        $user = new User();
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(false);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresUserWithoutPasswordReset(): void
    {
        $user = new User();
        $user->setEnabled(true);
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestRedirectsToPasswordWizard(): void
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setRequiresPasswordReset();
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('wizard_password')
            ->willReturn('/wizard/password');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent('/dashboard');

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/wizard/password', $response->headers->get('Location'));
    }

    /**
     * @return iterable<array{string, string|null}>
     */
    public static function provideBypassAttempts(): iterable
    {
        // see GHSA: the raw request URI must never be used to detect exempted routes
        yield ['/en/dashboard/?probe=/register/', 'dashboard'];
        yield ['/en/dashboard/?probe=/wizard/', 'dashboard'];
        yield ['/en/dashboard/?probe=/ReGiStEr/', 'dashboard'];
        yield ['/en/dashboard/?probe=/WiZaRd/password', 'dashboard'];
        yield ['/en/profile/u_reset/create-access-token?probe=/register/', 'user_profile_access_token'];
        yield ['/en/profile/u_reset/create-access-token?probe=/wizard/', 'user_profile_access_token'];
        yield ['/en/wizard/password/../../dashboard', 'dashboard'];
        yield ['/en/register/../dashboard', 'dashboard'];
        yield ['/en/dashboard/?probe=/api/', 'dashboard'];
        // no matched route (fail closed)
        yield ['/en/dashboard/?probe=/register/', null];
        yield ['/en/dashboard/', null];
    }

    #[DataProvider('provideBypassAttempts')]
    public function testOnKernelRequestRedirectsDespiteBypassAttempt(string $uri, ?string $route): void
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setRequiresPasswordReset();
        $token = $this->createUserToken($user);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())->method('generate')->with('wizard_password')->willReturn('/en/wizard/password');

        $security = $this->createMock(AuthorizationCheckerInterface::class);
        $security->expects($this->once())->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $sut = new PasswordResetSubscriber($urlGenerator, $security, $storage);
        $event = $this->createRequestEvent($uri, true, $route);

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/en/wizard/password', $response->headers->get('Location'));
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function provideAllowedRoutes(): iterable
    {
        yield ['logout', true];
        yield ['wizard_next', true];
        yield ['wizard_password', true];
        yield ['wizard_intro', true];
        yield ['registration_register', true];
        yield ['registration_confirm', true];
        yield ['registration_confirmed', true];
        yield ['user_registration_check_email', true];
        yield ['dashboard', false];
        yield ['user_profile_access_token', false];
        yield ['user_profile_password', false];
        yield ['wizard', false];
        yield ['Wizard_intro', false];
        yield ['my_wizard_intro', false];
        yield ['', false];
    }

    #[DataProvider('provideAllowedRoutes')]
    public function testIsAllowedRoute(string $route, bool $expected): void
    {
        self::assertSame($expected, PasswordResetSubscriber::isAllowedRoute($route));
    }

    private function createUserToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        return $token;
    }

    private function createRequestEvent(string $uri, bool $mainRequest = true, ?string $route = 'dashboard'): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($uri);
        if ($route !== null) {
            $request->attributes->set('_route', $route);
        }

        return new RequestEvent($kernel, $request, $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST);
    }
}

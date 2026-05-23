<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\LocaleService;
use App\Entity\User;
use App\EventSubscriber\UserEnvironmentSubscriber;
use App\Twig\LocaleFormatExtensions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(UserEnvironmentSubscriber::class)]
class UserEnvironmentSubscriberTest extends TestCase
{
    private string $defaultLocale;
    private string $defaultTimezone;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
        date_default_timezone_set($this->defaultTimezone);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([
            KernelEvents::REQUEST => ['prepareEnvironment', -10],
            KernelEvents::FINISH_REQUEST => ['restoreLocale', -20],
        ], UserEnvironmentSubscriber::getSubscribedEvents());
    }

    public function testPrepareEnvironmentUsesRequestLocaleWithoutAuthenticatedUser(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->expects($this->never())->method('isGranted');

        $localeExtension = $this->createLocaleFormatExtensions();
        $sut = new UserEnvironmentSubscriber($storage, $auth, $localeExtension);

        $sut->prepareEnvironment($this->createRequestEvent('fr', true));

        self::assertSame('fr', \Locale::getDefault());
        self::assertSame('fr', $localeExtension->getLocale());
        self::assertSame($this->defaultTimezone, date_default_timezone_get());
    }

    public function testPrepareEnvironmentUsesUserLocaleTimezoneAndPermission(): void
    {
        $user = new User();
        $user->setLocale('de');
        $user->setTimezone('Europe/Berlin');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->expects($this->once())->method('isGranted')->with('view_all_data')->willReturn(true);

        $localeExtension = $this->createLocaleFormatExtensions();
        $sut = new UserEnvironmentSubscriber($storage, $auth, $localeExtension);

        $sut->prepareEnvironment($this->createRequestEvent('en', true));

        self::assertSame('de', \Locale::getDefault());
        self::assertSame('de', $localeExtension->getLocale());
        self::assertSame('Europe/Berlin', date_default_timezone_get());
        self::assertTrue($user->canSeeAllData());
    }

    public function testRestoreLocaleAfterSubRequest(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->expects($this->never())->method('isGranted');

        $localeExtension = $this->createLocaleFormatExtensions();
        $sut = new UserEnvironmentSubscriber($storage, $auth, $localeExtension);

        $sut->prepareEnvironment($this->createRequestEvent('de', true));

        \Locale::setDefault('it');
        $localeExtension->setLocale('it');

        $sut->restoreLocale($this->createFinishRequestEvent(false));

        self::assertSame('de', \Locale::getDefault());
        self::assertSame('de', $localeExtension->getLocale());
    }

    private function createLocaleFormatExtensions(): LocaleFormatExtensions
    {
        return new LocaleFormatExtensions(new LocaleService([
            'de' => LocaleService::DEFAULT_SETTINGS,
            'en' => LocaleService::DEFAULT_SETTINGS,
            'fr' => LocaleService::DEFAULT_SETTINGS,
            'it' => LocaleService::DEFAULT_SETTINGS,
        ]));
    }

    private function createRequestEvent(string $locale, bool $mainRequest): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->setLocale($locale);

        return new RequestEvent($kernel, $request, $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST);
    }

    private function createFinishRequestEvent(bool $mainRequest): FinishRequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        return new FinishRequestEvent($kernel, $request, $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST);
    }
}

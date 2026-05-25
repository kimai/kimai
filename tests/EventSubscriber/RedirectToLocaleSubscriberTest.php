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
use App\EventSubscriber\RedirectToLocaleSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[CoversClass(RedirectToLocaleSubscriber::class)]
class RedirectToLocaleSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([KernelEvents::REQUEST => ['onKernelRequest', 0]], RedirectToLocaleSubscriber::getSubscribedEvents());
    }

    public function testOnKernelRequestIgnoresNonHomepageRequest(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->never())->method('getToken');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $sut = new RedirectToLocaleSubscriber($urlGenerator, $this->createLocaleService(), $storage);
        $event = $this->createRequestEvent('/de');

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestIgnoresHomepageWithSameHostReferer(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->never())->method('getToken');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->never())->method('generate');

        $sut = new RedirectToLocaleSubscriber($urlGenerator, $this->createLocaleService(), $storage);
        $event = $this->createRequestEvent('/', ['referer' => 'https://www.kimai.test/de/dashboard']);

        $sut->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestRedirectsAuthenticatedUserToLanguage(): void
    {
        $user = new User();
        $user->setLanguage('fr');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn($token);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('homepage', ['_locale' => 'fr'])
            ->willReturn('/fr');

        $sut = new RedirectToLocaleSubscriber($urlGenerator, $this->createLocaleService(), $storage);
        $event = $this->createRequestEvent('/');

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/fr', $response->headers->get('Location'));
    }

    public function testOnKernelRequestRedirectsAnonymousUserToPreferredBrowserLanguage(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('homepage', ['_locale' => 'de'])
            ->willReturn('/de');

        $sut = new RedirectToLocaleSubscriber($urlGenerator, $this->createLocaleService(), $storage);
        $event = $this->createRequestEvent('/', ['Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8']);

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/de', $response->headers->get('Location'));
    }

    public function testOnKernelRequestFallsBackToDefaultLocaleForAnonymousUser(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getToken')->willReturn(null);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('homepage', ['_locale' => 'en'])
            ->willReturn('/en');

        $sut = new RedirectToLocaleSubscriber($urlGenerator, $this->createLocaleService(), $storage);
        $event = $this->createRequestEvent('/', ['Accept-Language' => 'es-ES,es;q=0.9']);

        $sut->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/en', $response->headers->get('Location'));
    }

    private function createLocaleService(): LocaleService
    {
        return new LocaleService([
            'de' => [...LocaleService::DEFAULT_SETTINGS, 'translation' => true],
            'en' => [...LocaleService::DEFAULT_SETTINGS, 'translation' => true],
            'fr' => [...LocaleService::DEFAULT_SETTINGS, 'translation' => true],
        ]);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createRequestEvent(string $uri, array $headers = []): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($uri, 'GET', [], [], [], ['HTTP_HOST' => 'www.kimai.test', 'HTTPS' => 'on']);

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}

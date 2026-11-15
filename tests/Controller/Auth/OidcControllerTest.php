<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Auth;

use App\Configuration\OidcConfigurationInterface;
use App\Controller\Auth\OidcController;
use App\Oidc\OidcAuthenticator;
use App\Oidc\OidcClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(OidcController::class)]
class OidcControllerTest extends TestCase
{
    public function testLoginActionThrowsWhenDeactivated(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('OIDC deactivated');

        $sut = $this->createSut($this->createMock(OidcClient::class), false);

        $sut->loginAction($this->createRequest());
    }

    public function testLoginActionRedirectsToAuthorizationUrl(): void
    {
        $oidcClient = $this->createMock(OidcClient::class);
        $oidcClient
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->willReturn('https://id.example.com/authorize?client_id=my-client');

        $sut = $this->createSut($oidcClient, true);
        $request = $this->createRequest();

        $response = $sut->loginAction($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://id.example.com/authorize?client_id=my-client', $response->getTargetUrl());

        $session = $request->getSession();
        self::assertNotEmpty($session->get(OidcAuthenticator::SESSION_STATE));
        self::assertNotEmpty($session->get(OidcAuthenticator::SESSION_NONCE));
        self::assertNotEmpty($session->get(OidcAuthenticator::SESSION_PKCE));
    }

    public function testCallbackActionThrowsWhenDeactivated(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('OIDC deactivated');

        $sut = $this->createSut($this->createMock(OidcClient::class), false);

        $sut->callbackAction();
    }

    public function testCallbackActionThrowsRuntimeExceptionWhenActivated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must configure the check path to be handled by the firewall.');

        $sut = $this->createSut($this->createMock(OidcClient::class), true);

        $sut->callbackAction();
    }

    private function createSut(OidcClient $oidcClient, bool $activated): OidcController
    {
        $configuration = $this->createConfiguration($activated);

        $controller = new OidcController($oidcClient, $configuration);

        $container = new Container();
        $container->set('router', $this->createUrlGenerator());
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * @return OidcConfigurationInterface&MockObject
     */
    private function createConfiguration(bool $activated): OidcConfigurationInterface
    {
        $configuration = $this->createMock(OidcConfigurationInterface::class);
        $configuration->method('isActivated')->willReturn($activated);

        return $configuration;
    }

    private function createRequest(): Request
    {
        $request = Request::create('/oidc/login', 'GET');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function createUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static fn ($name): string => (string) $name);

        return $urlGenerator;
    }
}

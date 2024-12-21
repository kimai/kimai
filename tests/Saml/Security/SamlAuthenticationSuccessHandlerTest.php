<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\Security;

use App\Entity\User;
use App\Saml\SamlToken;
use App\Saml\Security\SamlAuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * @covers \App\Saml\Security\SamlAuthenticationSuccessHandler
 */
class SamlAuthenticationSuccessHandlerTest extends TestCase
{
    public function testRelayState(): void
    {
        $handler = new SamlAuthenticationSuccessHandler(new HttpUtils($this->getUrlGenerator()));
        $response = $handler->onAuthenticationSuccess($this->getRequest('/sso/login', 'http://localhost/relayed'), $this->getSamlToken());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue($response->isRedirect('http://localhost/relayed'));
    }

    public function testWithoutRelayState(): void
    {
        $httpUtils = new HttpUtils($this->getUrlGenerator());
        $handler = new SamlAuthenticationSuccessHandler($httpUtils);
        $defaultTargetPath = $httpUtils->generateUri($this->getRequest('/sso/login'), '/');
        $response = $handler->onAuthenticationSuccess($this->getRequest(), $this->getSamlToken());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue($response->isRedirect($defaultTargetPath));
    }

    public function testRelayStateLoop(): void
    {
        $httpUtils = new HttpUtils($this->getUrlGenerator());
        $handler = new SamlAuthenticationSuccessHandler($httpUtils);
        $loginPath = $httpUtils->generateUri($this->getRequest('/sso/login'), '/login');
        $response = $handler->onAuthenticationSuccess($this->getRequest($loginPath), $this->getSamlToken());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue(!$response->isRedirect($loginPath));
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $urlGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($name) {
                return (string) $name;
            })
        ;

        return $urlGenerator;
    }

    private function getRequest(string $path = '/', ?string $relayState = null): Request
    {
        $params = [];
        if (null !== $relayState) {
            $params['RelayState'] = $relayState;
        }

        return Request::create($path, 'get', $params);
    }

    private function getSamlToken(): SamlToken
    {
        $user = new User();
        $user->setUserIdentifier('admin');

        $token = new SamlToken($user, 'secured_area', []);
        $token->setAttributes(['foo' => 'bar']);

        return $token;
    }
}

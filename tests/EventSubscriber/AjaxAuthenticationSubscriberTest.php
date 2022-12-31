<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\AjaxAuthenticationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;

/**
 * @covers \App\EventSubscriber\AjaxAuthenticationSubscriber
 */
class AjaxAuthenticationSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = AjaxAuthenticationSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $methodName = $events[KernelEvents::EXCEPTION][0];
        $this->assertTrue(method_exists(AjaxAuthenticationSubscriber::class, $methodName));
    }

    public function getTestHeader()
    {
        yield ['XMLHttpRequest'];
        yield ['Kimai'];
    }

    /**
     * @dataProvider getTestHeader
     */
    public function testAuthenticationExpiredException(string $requestedWith)
    {
        $sut = new AjaxAuthenticationSubscriber();

        $exception = new AuthenticationExpiredException();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->initialize([], [], [], [], [], ['HTTP_X-Requested-With' => $requestedWith]);

        $event = new ExceptionEvent($kernel, $request, 1, $exception);

        $sut->onCoreException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertEquals('Session expired', $response->getContent());
        self::assertEquals(403, $response->getStatusCode());
        self::assertTrue($response->headers->has('Login-Required'));
        self::assertEquals('1', $response->headers->get('Login-Required'));
    }

    /**
     * @dataProvider getTestHeader
     */
    public function testAuthenticationException(string $requestedWith)
    {
        $sut = new AjaxAuthenticationSubscriber();

        $exception = new AuthenticationException();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->initialize([], [], [], [], [], ['HTTP_X-Requested-With' => $requestedWith]);

        $event = new ExceptionEvent($kernel, $request, 1, $exception);

        $sut->onCoreException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertEquals('Authentication problem', $response->getContent());
        self::assertEquals(403, $response->getStatusCode());
        self::assertTrue($response->headers->has('Login-Required'));
        self::assertEquals('1', $response->headers->get('Login-Required'));
    }

    /**
     * @dataProvider getTestHeader
     */
    public function testAccessDeniedException(string $requestedWith)
    {
        $sut = new AjaxAuthenticationSubscriber();

        $exception = new AccessDeniedException();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->initialize([], [], [], [], [], ['HTTP_X-Requested-With' => $requestedWith]);

        $event = new ExceptionEvent($kernel, $request, 1, $exception);

        $sut->onCoreException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertEquals('Access denied', $response->getContent());
        self::assertEquals(403, $response->getStatusCode());
        self::assertTrue($response->headers->has('Login-Required'));
        self::assertEquals('1', $response->headers->get('Login-Required'));
    }
}

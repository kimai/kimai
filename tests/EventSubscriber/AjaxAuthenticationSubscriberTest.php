<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AjaxAuthenticationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
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
    public function testGetSubscribedEvents(): void
    {
        $events = AjaxAuthenticationSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        /** @var string $methodName */
        $methodName = $events[KernelEvents::EXCEPTION][0];
        $this->assertTrue(method_exists(AjaxAuthenticationSubscriber::class, $methodName));
    }

    /**
     * @return array<array<string>>
     */
    public function getTestHeader(): array
    {
        return [
            ['XMLHttpRequest'],
            ['Kimai']
        ];
    }

    private function getSut(bool $loggedIn = false): AjaxAuthenticationSubscriber
    {
        $security = $this->createMock(Security::class);
        if ($loggedIn) {
            $user = new User();
            $security->method('getUser')->willReturn($user);
            $security->method('isGranted')->willReturn(true);
        }

        $sut = new AjaxAuthenticationSubscriber($security);

        return $sut;
    }

    /**
     * @dataProvider getTestHeader
     */
    public function testAuthenticationExpiredException(string $requestedWith): void
    {
        $sut = $this->getSut();

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
    public function testAuthenticationException(string $requestedWith): void
    {
        $sut = $this->getSut();

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
    public function testAccessDeniedException(string $requestedWith): void
    {
        $sut = $this->getSut();

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

    /**
     * @dataProvider getTestHeader
     */
    public function testAccessDeniedExceptionWithLoggedInUser(string $requestedWith): void
    {
        $sut = $this->getSut(true);

        $exception = new AccessDeniedException();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->initialize([], [], [], [], [], ['HTTP_X-Requested-With' => $requestedWith]);

        $event = new ExceptionEvent($kernel, $request, 1, $exception);

        $sut->onCoreException($event);

        self::assertNull($event->getResponse());
    }
}

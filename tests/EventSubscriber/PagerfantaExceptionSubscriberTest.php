<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\PagerfantaExceptionSubscriber;
use Pagerfanta\Exception\NotValidMaxPerPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @covers \App\EventSubscriber\PagerfantaExceptionSubscriber
 */
class PagerfantaExceptionSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = PagerfantaExceptionSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $methodName = $events[KernelEvents::EXCEPTION][0];
        $this->assertTrue(method_exists(PagerfantaExceptionSubscriber::class, $methodName));
    }

    public function testWithExceptions(): void
    {
        $sut = new PagerfantaExceptionSubscriber();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $exception = new \Exception();
        $requestType = HttpKernelInterface::MAIN_REQUEST;

        $event = new ExceptionEvent($kernel, $request, $requestType, $exception);
        $sut->onCoreException($event);
        self::assertSame($exception, $event->getThrowable());

        $event = new ExceptionEvent($kernel, $request, $requestType, new NotValidMaxPerPageException('Foo baaaaar!', 999));
        $sut->onCoreException($event);
        self::assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        self::assertEquals('Foo baaaaar!', $event->getThrowable()->getMessage());
        self::assertEquals(999, $event->getThrowable()->getCode());
        self::assertEquals(404, $event->getThrowable()->getStatusCode());

        $event = new ExceptionEvent($kernel, $request, $requestType, new OutOfRangeCurrentPageException('Trölölölölölö', 123));
        $sut->onCoreException($event);
        self::assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        self::assertEquals('Trölölölölölö', $event->getThrowable()->getMessage());
        self::assertEquals(123, $event->getThrowable()->getCode());
        self::assertEquals(404, $event->getThrowable()->getStatusCode());
    }
}

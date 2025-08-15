<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\LocaleService;
use App\EventSubscriber\RedirectToLocaleSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(RedirectToLocaleSubscriber::class)]
class RedirectToLocaleSubscriberTest extends TestCase
{
    public function testConstruct(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $sut = new RedirectToLocaleSubscriber($urlGenerator, new LocaleService(['de' => LocaleService::DEFAULT_SETTINGS, 'en' => LocaleService::DEFAULT_SETTINGS]));

        self::assertEquals([KernelEvents::REQUEST => ['onKernelRequest']], RedirectToLocaleSubscriber::getSubscribedEvents());

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getPathInfo')->willReturn('/de');

        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $sut->onKernelRequest($event);
    }
}

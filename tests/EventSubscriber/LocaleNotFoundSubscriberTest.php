<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\LocaleService;
use App\EventSubscriber\LocaleNotFoundSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(LocaleNotFoundSubscriber::class)]
class LocaleNotFoundSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([KernelEvents::EXCEPTION => ['onKernelException', 10]], LocaleNotFoundSubscriber::getSubscribedEvents());
    }

    /**
     * @return array<int, array{string, string}>
     */
    public static function getRedirectData(): array
    {
        return [
            ['/de_DE/timesheet/', '/de/timesheet/'],
            ['/de_AT/timesheet/?page=2&size=50', '/de/timesheet/?page=2&size=50'],
            ['/de_DE', '/de/'],
            ['/en_AU/admin/user/1/edit', '/en/admin/user/1/edit'],
            // unknown region, but known base
            ['/de_XX/dashboard/', '/de/dashboard/'],
        ];
    }

    #[DataProvider('getRedirectData')]
    public function testRedirectsNotTranslatedLocale(string $path, string $expected): void
    {
        $event = $this->createExceptionEvent($path, new NotFoundHttpException());

        $this->createSubscriber()->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals($expected, $response->getTargetUrl());
    }

    public function testRedirectKeepsBaseUrl(): void
    {
        $request = Request::create('/kimai/index.php/de_DE/timesheet/', 'GET', [], [], [], ['SCRIPT_FILENAME' => '/var/www/kimai/index.php', 'SCRIPT_NAME' => '/kimai/index.php']);
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException());

        $this->createSubscriber()->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/kimai/index.php/de/timesheet/', $response->getTargetUrl());
    }

    /**
     * @return array<int, array{string}>
     */
    public static function getIgnoredData(): array
    {
        return [
            // translated locales are handled by the router
            ['/de/foo/'],
            ['/de_CH/foo/'],
            ['/zh_Hant_TW/foo/'],
            // not a locale
            ['/api/users/'],
            ['/foo_BAR/'],
            ['/xx_YY/timesheet/'],
            ['/zh_Hant_XX/timesheet/'],
            ['/'],
            ['/de_DE_/timesheet/'],
        ];
    }

    #[DataProvider('getIgnoredData')]
    public function testIgnoresOtherPaths(string $path): void
    {
        $event = $this->createExceptionEvent($path, new NotFoundHttpException());

        $this->createSubscriber()->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresOtherExceptions(): void
    {
        $event = $this->createExceptionEvent('/de_DE/timesheet/', new AccessDeniedHttpException());

        $this->createSubscriber()->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    private function createSubscriber(): LocaleNotFoundSubscriber
    {
        return new LocaleNotFoundSubscriber(new LocaleService([
            'de' => ['date' => 'dd.MM.y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => true],
            'de_AT' => ['date' => 'dd.MM.y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => false],
            'de_CH' => ['date' => 'dd.MM.y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => true],
            'de_DE' => ['date' => 'dd.MM.y', 'time' => 'HH:mm', 'rtl' => false, 'translation' => false],
            'en' => ['date' => 'M/d/y', 'time' => 'h:mm a', 'rtl' => false, 'translation' => true],
            'en_AU' => ['date' => 'd/M/y', 'time' => 'h:mm a', 'rtl' => false, 'translation' => false],
            'zh_Hant' => ['date' => 'y/M/d', 'time' => 'HH:mm', 'rtl' => false, 'translation' => true],
            'zh_Hant_TW' => ['date' => 'y/M/d', 'time' => 'HH:mm', 'rtl' => false, 'translation' => true],
        ]));
    }

    private function createExceptionEvent(string $uri, \Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent($this->createMock(HttpKernelInterface::class), Request::create($uri), HttpKernelInterface::MAIN_REQUEST, $throwable);
    }
}

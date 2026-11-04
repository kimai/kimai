<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\LocaleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The locale in the URL is used for translating the UI, so only translated locales are accepted by the router.
 * Old bookmarks, mails and profiles might still contain a locale with region (e.g. /de_DE/timesheet/), which
 * would end in a 404. This listener redirects those URLs to the nearest translated locale (e.g. /de/timesheet/).
 */
final class LocaleNotFoundSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // runs before the ErrorListener (priority -128) renders the 404 page
            KernelEvents::EXCEPTION => ['onKernelException', 10]
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        // only locales with a region are interesting, base locales are either translated or unknown
        if (preg_match('#^/([a-z]{2}(?:_[A-Za-z]{2,4})+)(/.*)?$#', $request->getPathInfo(), $matches) !== 1) {
            return;
        }

        $locale = $matches[1];
        $nearest = $this->getNearestTranslationLocale($locale);
        if ($nearest === null || $nearest === $locale) {
            return;
        }

        $url = $request->getBaseUrl() . '/' . $nearest . ($matches[2] ?? '/');
        if (($query = $request->getQueryString()) !== null) {
            $url .= '?' . $query;
        }

        $event->setResponse(new RedirectResponse($url, Response::HTTP_FOUND));
    }

    /**
     * Returns null for locales that are not supported by Kimai at all, so we do not redirect garbage to /en/.
     */
    private function getNearestTranslationLocale(string $locale): ?string
    {
        if (!$this->localeService->isKnownLocale($locale) && !$this->localeService->isKnownLocale(explode('_', $locale)[0])) {
            return null;
        }

        return $this->localeService->getNearestTranslationLocale($locale);
    }
}

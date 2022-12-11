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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * When visiting the homepage, this listener redirects the user to the most
 * appropriate localized version according to the browser settings.
 *
 * See http://symfony.com/doc/current/components/http_kernel/introduction.html#the-kernel-request-event
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
final class RedirectToLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator, private LocaleService $localeService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest']
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ignore sub-requests and all URLs but the homepage
        if ('/' !== $request->getPathInfo()) {
            return;
        }

        // Ignore requests from referrers with the same HTTP host in order to prevent
        // changing language for users who possibly already selected it for this application.
        $referer = $request->headers->get('referer');
        if ($referer !== null && 0 === stripos($referer, $request->getSchemeAndHttpHost())) {
            return;
        }

        $allLanguages = $this->localeService->getAllLocales();

        // Add the default locale at the first position of the array, because getPreferredLanguage()
        // returns the first element when no appropriate language is found
        array_unshift($allLanguages, $this->localeService->getDefaultLocale());

        $preferredLanguage = $request->getPreferredLanguage(array_unique($allLanguages));

        $response = new RedirectResponse($this->urlGenerator->generate('homepage', ['_locale' => $preferredLanguage]));
        $event->setResponse($response);
    }
}

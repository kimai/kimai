<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Twig\LocaleFormatExtensions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserEnvironmentSubscriber implements EventSubscriberInterface
{
    private ?string $userLocale = null;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $auth,
        private readonly LocaleFormatExtensions $localeFormatExtensions
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // runs as first one in Kimai, to make sure we use the correct locales for rendering
            KernelEvents::REQUEST => ['prepareEnvironment', -10],
            // don't know why do we use -20
            KernelEvents::FINISH_REQUEST => ['restoreLocale', -20],
        ];
    }

    public function restoreLocale(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            return;
        }

        if ($this->userLocale === null) {
            return;
        }

        // LocaleSwitcher (called by LocaleAwareListener) overwrites \Locale::getDefault() with the URL
        // locale during sub-requests. Restore both the PHP default and the Twig formatter locale to
        // the user's formatting locale that was saved during the main request.
        \Locale::setDefault($this->userLocale);
        $this->localeFormatExtensions->setLocale($this->userLocale);
    }

    public function prepareEnvironment(RequestEvent $event): void
    {
        // ignore sub-requests
        if (!$event->isMainRequest()) {
            return;
        }

        $locale = $event->getRequest()->getLocale();

        // events like the toolbar might not have a token
        if (null !== ($token = $this->tokenStorage->getToken())) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $locale = $user->getLocale();
                date_default_timezone_set($user->getTimezone());
                $user->initCanSeeAllData($this->auth->isGranted('view_all_data'));
            }
        }

        // the locale is primarily used for formatting values, so we depend on the user locale if available
        $this->userLocale = $locale;
        \Locale::setDefault($locale);
        $this->localeFormatExtensions->setLocale($locale);
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserEnvironmentSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage, private AuthorizationCheckerInterface $auth)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['prepareEnvironment', -100],
        ];
    }

    public function prepareEnvironment(RequestEvent $event): void
    {
        // ignore sub-requests
        if (!$event->isMainRequest()) {
            return;
        }

        // the locale depends on the request, not on the user configuration
        \Locale::setDefault($event->getRequest()->getLocale());

        // ignore events like the toolbar where we do not have a token
        if (null === ($token = $this->tokenStorage->getToken())) {
            return;
        }

        $user = $token->getUser();

        if ($user instanceof User) {
            date_default_timezone_set($user->getTimezone());
            $user->initCanSeeAllData($this->auth->isGranted('view_all_data'));
        }
    }
}

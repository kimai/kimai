<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\PrepareUserEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserEnvironmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $auth, EventDispatcherInterface $dispatcher)
    {
        $this->storage = $tokenStorage;
        $this->auth = $auth;
        $this->eventDispatcher = $dispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['prepareEnvironment', 200],
        ];
    }

    public function prepareEnvironment(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        // the locale depends on the request, not on the user configuration
        \Locale::setDefault($event->getRequest()->getLocale());

        if (null === $this->storage->getToken()) {
            return;
        }

        $user = $this->storage->getToken()->getUser();
        if (!($user instanceof User)) {
            return;
        }

        date_default_timezone_set($user->getTimezone());
        $user->initCanSeeAllData($this->auth->isGranted('view_all_data'));

        $event = new PrepareUserEvent($user);
        $this->eventDispatcher->dispatch($event);
    }
}

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

class UserEnvironmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $auth;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $auth)
    {
        $this->storage = $tokenStorage;
        $this->auth = $auth;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['prepareEnvironment', -100],
        ];
    }

    public function prepareEnvironment(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (null === $this->storage->getToken()) {
            return;
        }

        $user = $this->storage->getToken()->getUser();

        if ($user instanceof User) {
            date_default_timezone_set($user->getTimezone());
            \Locale::setDefault($user->getLocale());
            $user->initCanSeeAllData($this->auth->isGranted('view_all_data'));
        }
    }
}

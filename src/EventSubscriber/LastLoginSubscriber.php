<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\UserInteractiveLoginEvent;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LastLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserRepository $repository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // triggered for programmatic logins like "password reset" or "registration"
            UserInteractiveLoginEvent::class => 'onImplicitLogin',
            // We do not use the InteractiveLoginEvent because it is not triggered e.g. for SAML
            LoginSuccessEvent::class => 'onFormLogin',
        ];
    }

    public function onImplicitLogin(UserInteractiveLoginEvent $event): void
    {
        $user = $event->getUser();

        $user->setLastLogin(new \DateTime('now', new \DateTimeZone($user->getTimezone())));
        $this->repository->saveUser($user);
    }

    public function onFormLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            $user->setLastLogin(new \DateTime('now', new \DateTimeZone($user->getTimezone())));
            $this->repository->saveUser($user);
        }
    }
}

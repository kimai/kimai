<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Utils\MenuItemModel;
use KevinPapst\TablerBundle\Event\UserDetailsEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
class UserDetailsSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuthorizationCheckerInterface $auth, private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDetailsEvent::class => ['onUserDetailsEvent', 100],
        ];
    }

    public function onUserDetailsEvent(UserDetailsEvent $event): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        $event->setUser($user);

        if ($this->auth->isGranted('view', $user)) {
            $event->addLink(
                new MenuItemModel('user_profile', 'my.profile', 'user_profile', ['username' => $user->getUserIdentifier()], 'fas fa-tachometer-alt')
            );
        }

        if ($this->auth->isGranted('edit', $user)) {
            $event->addLink(
                new MenuItemModel('user_profile_edit', 'action.edit', 'user_profile_edit', ['username' => $user->getUserIdentifier()], 'fas fa-tachometer-alt')
            );
        }

        if ($this->auth->isGranted('preferences', $user)) {
            $event->addLink(
                new MenuItemModel('user_profile_preferences', 'profile.preferences', 'user_profile_preferences', ['username' => $user->getUserIdentifier()], 'fas fa-tachometer-alt')
            );
        }
    }
}

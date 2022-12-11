<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\User;
use App\Event\PageActionsEvent;

final class UserFormsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'user_forms';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var User $user */
        $user = $payload['user'];

        if ($user->getId() === null) {
            return;
        }

        if ($this->isGranted('edit', $user)) {
            $event->addAction('edit', ['url' => $this->path('user_profile_edit', ['username' => $user->getUserIdentifier()]), 'title' => 'edit', 'translation_domain' => 'actions']);
        }
        if ($this->isGranted('password', $user)) {
            $event->addAction('password', ['url' => $this->path('user_profile_password', ['username' => $user->getUserIdentifier()]), 'title' => 'profile.password']);
        }
        if ($this->isGranted('2fa', $user)) {
            $event->addAction('2fa', ['url' => $this->path('user_profile_2fa', ['username' => $user->getUserIdentifier()]), 'title' => 'profile.2fa']);
        }
        if ($this->isGranted('api-token', $user)) {
            $event->addAction('api-token', ['url' => $this->path('user_profile_api_token', ['username' => $user->getUserIdentifier()]), 'title' => 'profile.api-token']);
        }
        if ($this->isGranted('teams', $user)) {
            $event->addAction('teams', ['url' => $this->path('user_profile_teams', ['username' => $user->getUserIdentifier()]), 'title' => 'teams']);
        }
        if ($this->isGranted('roles', $user)) {
            $event->addAction('roles', ['url' => $this->path('user_profile_roles', ['username' => $user->getUserIdentifier()]), 'title' => 'profile.roles']);
        }
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Entity\User;
use App\Event\ActionsEvent;

class UserSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.user' => ['onActions', 1000],
        ];
    }

    public function onActions(ActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!isset($payload['user'])) {
            return;
        }

        /** @var User $user */
        $user = $payload['user'];

        if ($user->getId() === null) {
            return;
        }

        $actions = $event->getActions();

        if ($this->isGranted('view', $user)) {
            $actions['profile-stats'] = ['icon' => 'avatar', 'url' => $this->path('user_profile', ['username' => $user->getUsername()]), 'translation_domain' => 'actions'];
        }

        if (\count($actions) > 0) {
            $actions['divider'] = null;
        }

        $subActions = [];
        if ($this->isGranted('edit', $user)) {
            $subActions['edit'] = ['url' => $this->path('user_profile_edit', ['username' => $user->getUsername()]), 'title' => 'edit', 'translation_domain' => 'actions'];
        }
        if ($this->isGranted('preferences', $user)) {
            $subActions['settings'] = ['url' => $this->path('user_profile_preferences', ['username' => $user->getUsername()]), 'title' => 'settings', 'translation_domain' => 'actions'];
        }
        if ($this->isGranted('password', $user)) {
            $subActions['password'] = ['url' => $this->path('user_profile_password', ['username' => $user->getUsername()]), 'title' => 'profile.password'];
        }
        if ($this->isGranted('api-token', $user)) {
            $subActions['api-token'] = ['url' => $this->path('user_profile_api_token', ['username' => $user->getUsername()]), 'title' => 'profile.api-token'];
        }
        if ($this->isGranted('teams', $user)) {
            $subActions['teams'] = ['url' => $this->path('user_profile_teams', ['username' => $user->getUsername()]), 'title' => 'profile.teams'];
        }
        if ($this->isGranted('roles', $user)) {
            $subActions['roles'] = ['url' => $this->path('user_profile_roles', ['username' => $user->getUsername()]), 'title' => 'profile.roles'];
        }

        if (\count($subActions) > 0) {
            $actions['edit'] = ['children' => $subActions, 'title' => 'edit'];
            $actions['divider2'] = null;
        }

        $viewOther = $this->isGranted('view_other_timesheet');
        if ($this->isGranted('view_reporting')) {
            if ($viewOther || ($event->getUser()->getId() === $user->getId())) {
                $actions['menu.reporting'] = ['url' => $this->path('report_user_month', ['user' => $user->getId()]), 'icon' => 'reporting'];
            }
        }

        if ($viewOther && $user->isEnabled()) {
            $actions['timesheet'] = $this->path('admin_timesheet', ['users[]' => $user->getId()]);
        }

        $view = $payload['view'] ?? null;

        if ($view === 'index' && $this->isGranted('delete', $user)) {
            $actions['trash'] = ['url' => $this->path('admin_user_delete', ['id' => $user->getId()]), 'class' => 'modal-ajax-form'];
        }

        $payload['actions'] = array_merge($payload['actions'], $actions);

        $event->setPayload($payload);
    }
}

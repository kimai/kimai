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

class UserSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'user';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var User $user */
        $user = $payload['user'];

        if ($user->getId() === null) {
            return;
        }

        if ($this->isGranted('view', $user)) {
            $event->addAction('profile-stats', ['icon' => 'avatar', 'url' => $this->path('user_profile', ['username' => $user->getUsername()]), 'translation_domain' => 'actions']);
            $event->addDivider();
        }

        if ($this->isGranted('edit', $user)) {
            $event->addActionToSubmenu('edit', 'edit', ['url' => $this->path('user_profile_edit', ['username' => $user->getUsername()]), 'title' => 'edit', 'translation_domain' => 'actions']);
        }
        if ($this->isGranted('preferences', $user)) {
            $event->addActionToSubmenu('edit', 'settings', ['url' => $this->path('user_profile_preferences', ['username' => $user->getUsername()]), 'title' => 'settings', 'translation_domain' => 'actions']);
        }
        if ($this->isGranted('password', $user)) {
            $event->addActionToSubmenu('edit', 'password', ['url' => $this->path('user_profile_password', ['username' => $user->getUsername()]), 'title' => 'profile.password']);
        }
        if ($this->isGranted('api-token', $user)) {
            $event->addActionToSubmenu('edit', 'api-token', ['url' => $this->path('user_profile_api_token', ['username' => $user->getUsername()]), 'title' => 'profile.api-token']);
        }
        if ($this->isGranted('teams', $user)) {
            $event->addActionToSubmenu('edit', 'teams', ['url' => $this->path('user_profile_teams', ['username' => $user->getUsername()]), 'title' => 'profile.teams']);
        }
        if ($this->isGranted('roles', $user)) {
            $event->addActionToSubmenu('edit', 'roles', ['url' => $this->path('user_profile_roles', ['username' => $user->getUsername()]), 'title' => 'profile.roles']);
        }

        if ($event->hasSubmenu('edit')) {
            $event->addDivider();
        }

        $viewOther = $this->isGranted('view_other_timesheet');
        if ($this->isGranted('view_reporting')) {
            // also found in App\Controller\Reporting\ReportByUserController
            if (($viewOther && $this->isGranted('view_other_reporting')) || ($event->getUser()->getId() === $user->getId())) {
                $event->addAction('menu.reporting', ['url' => $this->path('report_user_month', ['user' => $user->getId()]), 'icon' => 'reporting']);
            }
        }

        if ($viewOther && $user->isEnabled()) {
            $event->addActionToSubmenu('filter', 'timesheet', ['title' => 'timesheet.filter', 'translation_domain' => 'actions', 'url' => $this->path('admin_timesheet', ['users[]' => $user->getId()])]);
        }

        if ($event->isIndexView() && $this->isGranted('delete', $user)) {
            $event->addDelete($this->path('admin_user_delete', ['id' => $user->getId()]));
        }
    }
}

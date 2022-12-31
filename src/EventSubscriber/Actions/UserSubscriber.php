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

final class UserSubscriber extends AbstractActionsSubscriber
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
            $event->addAction('profile-stats', ['icon' => 'avatar', 'url' => $this->path('user_profile', ['username' => $user->getUserIdentifier()]), 'translation_domain' => 'actions', 'title' => 'profile-stats']);
            $event->addDivider();
        }

        if ($this->isGranted('edit', $user)) {
            $event->addAction('edit', ['url' => $this->path('user_profile_edit', ['username' => $user->getUserIdentifier()]), 'title' => 'edit', 'translation_domain' => 'actions']);
        }

        if ($this->isGranted('preferences', $user)) {
            $event->addConfig($this->path('user_profile_preferences', ['username' => $user->getUserIdentifier()]));
        }

        if ($this->isGranted('report:other') || ($this->isGranted('report:user') && $event->getUser()->getId() === $user->getId())) {
            $event->addActionToSubmenu('report', 'weekly', ['url' => $this->path('report_user_week', ['user' => $user->getId()]), 'translation_domain' => 'reporting', 'title' => 'report_user_week']);
            $event->addActionToSubmenu('report', 'monthly', ['url' => $this->path('report_user_month', ['user' => $user->getId()]), 'translation_domain' => 'reporting', 'title' => 'report_user_month']);
            $event->addActionToSubmenu('report', 'yearly', ['url' => $this->path('report_user_year', ['user' => $user->getId()]), 'translation_domain' => 'reporting', 'title' => 'report_user_year']);
        }

        if ($this->isGranted('view_other_timesheet') && $user->isEnabled()) {
            $event->addActionToSubmenu('filter', 'timesheet', ['url' => $this->path('admin_timesheet', ['users[]' => $user->getId()]), 'title' => 'timesheet.filter', 'translation_domain' => 'actions']);
        }

        if ($this->isGranted('view_team')) {
            $event->addActionToSubmenu('filter', 'teams', ['url' => $this->path('admin_team', ['users[]' => $user->getId()]), 'title' => 'teams']);
        }

        if ($event->isIndexView() && $this->isGranted('delete', $user)) {
            $event->addDelete($this->path('admin_user_delete', ['id' => $user->getId()]));
        }
    }
}

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
            $event->addAction('profile-stats', ['icon' => 'avatar', 'url' => $this->path('user_profile', ['username' => $user->getUsername()]), 'translation_domain' => 'actions', 'title' => 'profile-stats']);
            $event->addDivider();
        }

        if ($this->isGranted('edit', $user)) {
            $event->addAction('edit', ['url' => $this->path('user_profile_edit', ['username' => $user->getUsername()]), 'title' => 'edit', 'translation_domain' => 'actions']);
        }

        if ($this->isGranted('preferences', $user)) {
            $event->addAction('settings', ['url' => $this->path('user_profile_preferences', ['username' => $user->getUsername()]), 'icon' => 'settings', 'title' => 'settings', 'translation_domain' => 'actions']);
        }

        $viewOther = $this->isGranted('view_other_timesheet');
        if ($this->isGranted('view_reporting')) {
            // also found in App\Controller\Reporting\ReportByUserController
            if (($viewOther && $this->isGranted('view_other_reporting')) || ($event->getUser()->getId() === $user->getId())) {
                $event->addAction('reporting', ['url' => $this->path('report_user_month', ['user' => $user->getId()]), 'icon' => 'reporting', 'title' => 'menu.reporting']);
            }
        }

        if ($viewOther && $user->isEnabled()) {
            $event->addActionToSubmenu('filter', 'timesheet', ['url' => $this->path('admin_timesheet', ['users[]' => $user->getId()]), 'title' => 'timesheet.filter', 'translation_domain' => 'actions']);
        }

        if ($event->isIndexView() && $this->isGranted('delete', $user)) {
            $event->addDelete($this->path('admin_user_delete', ['id' => $user->getId()]));
        }
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class UsersSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.users' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event): void
    {
        $event->addSearchToggle();
        if ($event->isIndexView()) {
            $event->addColumnToggle('#modal_user_admin');
        }
        $event->addQuickExport($this->path('user_export'));

        if ($this->isGranted('create_user')) {
            $event->addCreate($this->path('admin_user_create'), false);
        }

        $event->addHelp($this->documentationLink('users.html'));
    }
}

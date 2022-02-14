<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\UserQuery;

class UsersSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'users';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var UserQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);

        if ($event->isIndexView()) {
            $event->addColumnToggle('#modal_user_admin');
        }

        $event->addQuickExport($this->path('user_export'));

        if ($this->isGranted('create_user')) {
            $event->addCreate($this->path('admin_user_create'), false);
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'user']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('users.html'));
    }
}

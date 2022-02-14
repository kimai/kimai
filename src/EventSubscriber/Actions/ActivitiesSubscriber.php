<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\ActivityQuery;

class ActivitiesSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'activities';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var ActivityQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);
        $event->addColumnToggle('#modal_activity_admin');
        $event->addQuickExport($this->path('activity_export'));

        if ($this->isGranted('create_activity')) {
            $event->addCreate($this->path('admin_activity_create'));
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'activity']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('activity.html'));
    }
}

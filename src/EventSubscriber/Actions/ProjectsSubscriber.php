<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Repository\Query\ProjectQuery;

class ProjectsSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'projects';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var ProjectQuery $query */
        $query = $payload['query'];

        $event->addSearchToggle($query);

        $event->addColumnToggle('#modal_project_admin');
        $event->addQuickExport($this->path('project_export'));

        if ($this->isGranted('create_project')) {
            $event->addCreate($this->path('admin_project_create'));
        }

        if ($this->isGranted('system_configuration')) {
            $event->addAction('settings', ['url' => $this->path('system_configuration_section', ['section' => 'project']), 'class' => 'modal-ajax-form']);
        }

        $event->addHelp($this->documentationLink('project.html'));
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Export\ServiceExport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class TimesheetsTeamSubscriber extends AbstractActionsSubscriber
{
    public function __construct(AuthorizationCheckerInterface $auth, UrlGeneratorInterface $urlGenerator, private readonly ServiceExport $serviceExport)
    {
        parent::__construct($auth, $urlGenerator);
    }

    public static function getActionName(): string
    {
        return 'timesheets_team';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('create_other_timesheet')) {
            $event->addAction('create', ['title' => 'create', 'url' => $this->path('admin_timesheet_create'), 'class' => 'create-ts modal-ajax-form']);
            $event->addAction('multi-user', ['title' => 'create-timesheet-multiuser', 'url' => $this->path('admin_timesheet_create_multiuser'), 'class' => 'create-ts-mu modal-ajax-form', 'icon' => 'fas fa-user-plus']);
        }

        if ($this->isGranted('export_other_timesheet')) {
            foreach ($this->serviceExport->getTimesheetExporter() as $exporter) {
                $event->addActionToSubmenu('export', $exporter->getId(), ['url' => $this->path('admin_timesheet_export', ['exporter' => $exporter->getId()]), 'class' => 'toolbar-action', 'title' => 'button.' . $exporter->getId(), 'translation_domain' => 'messages']);
            }
        }
    }
}

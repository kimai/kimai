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

final class TimesheetsSubscriber extends AbstractActionsSubscriber
{
    public function __construct(AuthorizationCheckerInterface $auth, UrlGeneratorInterface $urlGenerator, private readonly ServiceExport $serviceExport)
    {
        parent::__construct($auth, $urlGenerator);
    }

    public static function getActionName(): string
    {
        return 'timesheets';
    }

    public function onActions(PageActionsEvent $event): void
    {
        if ($this->isGranted('create_own_timesheet')) {
            $event->addCreate($this->path('timesheet_create'));
        }

        if ($this->isGranted('export_own_timesheet')) {
            foreach ($this->serviceExport->getTimesheetExporter() as $exporter) {
                $event->addActionToSubmenu('export', $exporter->getId(), ['url' => $this->path('timesheet_export', ['exporter' => $exporter->getId()]), 'class' => 'toolbar-action', 'title' => 'button.' . $exporter->getId(), 'translation_domain' => 'messages']);
            }
        }
    }
}

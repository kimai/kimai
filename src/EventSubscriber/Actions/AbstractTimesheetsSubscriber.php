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

abstract class AbstractTimesheetsSubscriber extends AbstractActionsSubscriber
{
    private $serviceExport;

    public function __construct(AuthorizationCheckerInterface $security, UrlGeneratorInterface $urlGenerator, ServiceExport $serviceExport)
    {
        parent::__construct($security, $urlGenerator);
        $this->serviceExport = $serviceExport;
    }

    protected function addExporter(PageActionsEvent $event, string $routeExport): void
    {
        $allExporter = $this->serviceExport->getTimesheetExporter();
        if (\count($allExporter) === 1) {
            $event->addAction('download', ['url' => $this->path($routeExport, ['exporter' => $allExporter[0]->getId()]), 'class' => 'toolbar-action']);
        } else {
            foreach ($allExporter as $exporter) {
                $id = $exporter->getId();
                $event->addActionToSubmenu('download', 'exporter.' . $id, ['title' => 'button.' . $id, 'url' => $this->path($routeExport, ['exporter' => $id]), 'class' => 'toolbar-action exporter-' . $id]);
            }
        }
    }
}

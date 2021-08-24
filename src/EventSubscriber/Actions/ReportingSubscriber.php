<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Reporting\Report;
use App\Reporting\ReportingService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReportingSubscriber extends AbstractActionsSubscriber
{
    private $reportingService;

    public function __construct(AuthorizationCheckerInterface $security, UrlGeneratorInterface $urlGenerator, ReportingService $reportingService)
    {
        parent::__construct($security, $urlGenerator);
        $this->reportingService = $reportingService;
    }

    public static function getActionName(): string
    {
        return 'reporting';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $reports = $this->reportingService->getAvailableReports($event->getUser());

        foreach ($reports as $report) {
            $subMenu = 'reporting';
            if ($report instanceof Report) {
                $subMenu = $report->getReportIcon();
            }
            $event->addActionToSubmenu($subMenu, $report->getId(), ['title' => $report->getLabel(), 'translation_domain' => 'reporting', 'url' => $this->path($report->getRoute()), 'class' => 'report-' . $report->getId()]);
        }

        $event->addHelp($this->documentationLink('reporting.html'));
    }
}

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

class ReportingViewsSubscriber extends AbstractActionsSubscriber
{
    private $reportingService;

    public function __construct(AuthorizationCheckerInterface $security, UrlGeneratorInterface $urlGenerator, ReportingService $reportingService)
    {
        parent::__construct($security, $urlGenerator);
        $this->reportingService = $reportingService;
    }

    public static function getActionName(): string
    {
        return 'reporting_views';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $reports = $this->reportingService->getAvailableReports($event->getUser());

        $subMenu = null;
        $lastSubmenu = null;

        foreach ($reports as $report) {
            $subMenu = null;
            if ($report instanceof Report) {
                $subMenu = $report->getReportIcon();
                if ($lastSubmenu === null) {
                    $lastSubmenu = $subMenu;
                }
            }
            if ($subMenu !== $lastSubmenu) {
                $lastSubmenu = $subMenu;
                $event->addDivider();
            }
            $event->addAction($report->getId(), ['title' => $report->getLabel(), 'translation_domain' => 'reporting', 'url' => $this->path($report->getRoute()), 'class' => 'report-' . $report->getId()]);
        }
    }
}

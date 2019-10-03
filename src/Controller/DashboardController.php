<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Event\DashboardEvent;
use App\Widget\Type\AbstractContainer;
use App\Widget\Type\AuthorizedWidget;
use App\Widget\Type\CompoundRow;
use App\Widget\WidgetContainerInterface;
use App\Widget\WidgetException;
use App\Widget\WidgetService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dashboard controller for the admin area.
 *
 * @Route(path="/dashboard")
 * @Security("is_granted('ROLE_USER')")
 */
class DashboardController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var WidgetService
     */
    protected $widgets;
    /**
     * @var array
     */
    protected $dashboard;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param WidgetService $service
     * @param array $dashboard
     */
    public function __construct(EventDispatcherInterface $dispatcher, WidgetService $service, array $dashboard)
    {
        $this->eventDispatcher = $dispatcher;
        $this->widgets = $service;
        $this->dashboard = $dashboard;
    }

    /**
     * @Route(path="/", defaults={}, name="dashboard", methods={"GET"})
     */
    public function indexAction()
    {
        $event = new DashboardEvent($this->getUser());

        foreach ($this->dashboard as $widgetRow) {
            if (empty($widgetRow['widgets'])) {
                continue;
            }

            if (null !== $widgetRow['permission'] && !$this->isGranted($widgetRow['permission'])) {
                continue;
            }

            if (!isset($widgetRow['type'])) {
                $widgetRow['type'] = CompoundRow::class;
            }

            if (!class_exists($widgetRow['type'])) {
                throw new WidgetException(sprintf('Unknown widget type "%s"', $widgetRow['type']));
            }

            $row = new $widgetRow['type']();
            if (!($row instanceof AbstractContainer)) {
                throw new WidgetException(
                    sprintf(
                        'Expected widget type to be an instanceof "%s", but found "%s"',
                        AbstractContainer::class,
                        $widgetRow['type']
                    )
                );
            }

            $row->setTitle($widgetRow['title'] ?? '');
            $row->setOrder($widgetRow['order']);

            foreach ($widgetRow['widgets'] as $widgetName) {
                if (!$this->widgets->hasWidget($widgetName)) {
                    throw new \Exception(sprintf('Unknown widget "%s"', $widgetName));
                }

                $widget = $this->widgets->getWidget($widgetName);

                $add = true;
                if ($widget instanceof AuthorizedWidget) {
                    $tmp = false;
                    foreach ($widget->getPermissions() as $perm) {
                        if ($this->isGranted($perm)) {
                            $tmp = true;
                            break;
                        }
                    }
                    $add = $tmp;
                }

                if ($add) {
                    $row->addWidget($widget);
                }
            }

            $event->addSection($row);
        }

        $this->eventDispatcher->dispatch($event);

        $sections = $event->getSections();

        uasort(
            $sections,
            function (WidgetContainerInterface $a, WidgetContainerInterface $b) {
                if ($a->getOrder() == $b->getOrder()) {
                    return 0;
                }

                return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
            }
        );

        return $this->render('dashboard/index.html.twig', [
            'widgets' => $sections
        ]);
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Event\DashboardEvent;
use App\Model\DashboardSection;
use App\Repository\WidgetRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Dashboard controller for the admin area.
 *
 * @Route(path="/dashboard")
 * @Security("is_granted('ROLE_USER')")
 */
class DashboardController extends Controller
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var WidgetRepository
     */
    protected $repository;
    /**
     * @var array
     */
    protected $dashboard;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param WidgetRepository $repository
     * @param array $dashboard
     */
    public function __construct(EventDispatcherInterface $dispatcher, WidgetRepository $repository, array $dashboard)
    {
        $this->eventDispatcher = $dispatcher;
        $this->repository = $repository;
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

            if (!$this->isGranted($widgetRow['permission'])) {
                continue;
            }

            $row = new DashboardSection($widgetRow['title'] ?? null);
            $row
                ->setOrder($widgetRow['order'])
                ->setType($widgetRow['type'])
            ;

            foreach ($widgetRow['widgets'] as $widgetName) {
                if (!$this->repository->has($widgetName)) {
                    throw new \Exception('Unknwon widget: ' . $widgetName);
                }

                $row->addWidget($this->repository->get($widgetName, $event->getUser()));
            }

            $event->addSection($row);
        }

        $this->eventDispatcher->dispatch(
            DashboardEvent::DASHBOARD,
            $event
        );

        $sections = $event->getSections();

        uasort(
            $sections,
            function (DashboardSection $a, DashboardSection $b) {
                if ($a->getOrder() == $b->getOrder()) {
                    return 0;
                }

                return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
            }
        );

        return $this->render('dashboard/index.html.twig', [
            'widget_rows' => $sections
        ]);
    }
}

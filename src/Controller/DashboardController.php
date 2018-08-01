<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Event\DashboardEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dashboard controller for the admin area.
 *
 * @Route("/dashboard")
 * @Security("is_granted('ROLE_USER')")
 */
class DashboardController extends Controller
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @Route("/", defaults={}, name="dashboard")
     * @Method("GET")
     */
    public function indexAction()
    {
        $event = new DashboardEvent($this->getUser());

        $this->eventDispatcher->dispatch(
            DashboardEvent::DASHBOARD,
            $event
        );

        return $this->render('dashboard/index.html.twig', [
            'widget_rows' => $event->getWidgetRows()
        ]);
    }
}

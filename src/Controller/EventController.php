<?php

/*
 * This file is part of the AdminLTE bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Event\ThemeEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
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
     * @return EventDispatcherInterface
     */
    protected function getDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param string $eventName
     *
     * @return bool
     */
    protected function hasListener($eventName)
    {
        return $this->getDispatcher()->hasListeners($eventName);
    }

    /**
     * @param Request $request
     * @param string $eventName
     * @return ThemeEvent|Response
     */
    public function trigger(Request $request, string $event)
    {
        if (!$this->hasListener($event)) {
            return new Response();
        }

        $themeEvent = new ThemeEvent($request, $this->getUser());
        $this->getDispatcher()->dispatch($event, $themeEvent);

        return new Response($themeEvent->getContent());
    }
}

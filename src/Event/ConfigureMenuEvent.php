<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;

/**
 * The ConfigureMenuEvent is used for populating navigations.
 */
abstract class ConfigureMenuEvent extends Event
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var SidebarMenuEvent
     */
    private $event;

    /**
     * ConfigureMenuEvent constructor.
     * @param Request $request
     * @param SidebarMenuEvent $event
     */
    public function __construct(
        Request $request,
        SidebarMenuEvent $event
    ) {
        $this->request = $request;
        $this->event = $event;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return SidebarMenuEvent
     */
    public function getMenu()
    {
        return $this->event;
    }
}

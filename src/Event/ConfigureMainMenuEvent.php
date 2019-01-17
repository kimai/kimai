<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 */
class ConfigureMainMenuEvent extends Event
{
    public const CONFIGURE = 'app.main_menu_configure';

    /**
     * @var Request
     */
    private $request;
    /**
     * @var SidebarMenuEvent
     */
    private $event;

    /**
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

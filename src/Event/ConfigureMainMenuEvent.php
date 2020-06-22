<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 */
final class ConfigureMainMenuEvent extends Event
{
    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const CONFIGURE = ConfigureMainMenuEvent::class;

    /**
     * @var Request
     */
    private $request;
    /**
     * @var SidebarMenuEvent
     */
    private $event;
    /**
     * @var MenuItemModel
     */
    private $admin;
    /**
     * @var MenuItemModel
     */
    private $system;

    /**
     * @param Request $request
     * @param SidebarMenuEvent $event
     * @param MenuItemModel $admin
     * @param MenuItemModel $system
     */
    public function __construct(Request $request, SidebarMenuEvent $event, MenuItemModel $admin, MenuItemModel $system)
    {
        $this->request = $request;
        $this->event = $event;
        $this->admin = $admin;
        $this->system = $system;
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

    /**
     * @return MenuItemModel
     */
    public function getAdminMenu()
    {
        return $this->admin;
    }

    /**
     * @return MenuItemModel
     */
    public function getSystemMenu()
    {
        return $this->system;
    }
}

<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\EventListener;

use AppBundle\Event\ConfigureMainMenuEvent;
use AppBundle\Event\ConfigureAdminMenuEvent;

/**
 * Class Menu
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class Menu
{
    /**
     * @param \AppBundle\Event\ConfigureMainMenuEvent $event
     */
    public function onMainMenuConfigure(ConfigureMainMenuEvent $event)
    {
        $menu = $event->getMenu();

        $item = $menu->addChild('Timesheet', array('route' => 'timesheet'));
        $item->setLabel('menu.timesheet');
        $item->setChildrenAttribute('icon', 'clock-o');
    }

    /**
     * @param \AppBundle\Event\ConfigureAdminMenuEvent $event
     */
    public function onAdminMenuConfigure(ConfigureAdminMenuEvent $event)
    {
        $menu = $event->getMenu();

        $item = $menu->addChild('TimeAdmin', array('route' => 'timesheet'));
        //$item->setLabel('menu.admin_timesheet');
        $item->setChildrenAttribute('icon', 'clock-o');
    }
}
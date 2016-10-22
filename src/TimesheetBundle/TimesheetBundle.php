<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use TimesheetBundle\EventListener\Menu as MenuListener;

/**
 * This class defines the Bundle for all Timesheet related topics
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetBundle extends Bundle
{

    /**
     * Boots the Bundle.
     */
    public function boot()
    {
        $listener = new MenuListener();
        /* @var $dispatcher \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher */
        $dispatcher = $this->container->get('event_dispatcher');
    }
}

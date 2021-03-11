<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;

class SystemConfigurationSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.system_configuration' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $event->addHelp($this->documentationLink('configurations.html'));
    }
}

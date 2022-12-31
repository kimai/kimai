<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use App\Plugin\Plugin;

final class PluginSubscriber extends AbstractActionsSubscriber
{
    public static function getActionName(): string
    {
        return 'plugin';
    }

    public function onActions(PageActionsEvent $event): void
    {
        $payload = $event->getPayload();

        /** @var Plugin $plugin */
        $plugin = $payload['plugin'];

        $event->addAction('home', ['url' => $plugin->getMetadata()->getHomepage(), 'target' => '_blank']);
    }
}

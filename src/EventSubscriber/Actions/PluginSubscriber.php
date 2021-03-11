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

class PluginSubscriber extends AbstractActionsSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'actions.plugin' => ['onActions', 1000],
        ];
    }

    public function onActions(PageActionsEvent $event)
    {
        $payload = $event->getPayload();

        if (!\is_array($payload) || !\array_key_exists('plugin', $payload)) {
            return;
        }

        /** @var Plugin $plugin */
        $plugin = $payload['plugin'];

        if (!($plugin instanceof Plugin)) {
            return;
        }

        $event->addAction('home', ['url' => $plugin->getMetadata()->getHomepage(), 'target' => '_blank']);
    }
}

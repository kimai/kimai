<?php

/*
 * This file is part of the Kimai CustomCSSBundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ThemeEvent;
use App\Plugin\PluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginSubscriber implements EventSubscriberInterface
{
    /**
     * @var PluginManager
     */
    protected $plugins;

    /**
     * @param PluginManager $plugins
     */
    public function __construct(PluginManager $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ThemeEvent::CONTENT_START=> ['renderLicenseMessage', 100],
        ];
    }

    /**
     * @param ThemeEvent $event
     */
    public function renderLicenseMessage(ThemeEvent $event)
    {
        $names = [];
        foreach($this->plugins->getPlugins() as $id => $plugin) {
            $names[] = $plugin->getName();
        }

        $html = '
            <div class="callout callout-danger">
                <h4>License problems</h4>
                You are running the following plugins: ' . implode(', ', $names) . ' 
            </div>
        ';

        $event->addContent($html);
    }
}

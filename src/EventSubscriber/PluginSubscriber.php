<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\ThemeEvent;
use App\Plugin\PluginManager;
use App\Security\CurrentUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginSubscriber implements EventSubscriberInterface
{
    /**
     * @var PluginManager
     */
    protected $plugins;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var string[]
     */
    protected $unlicensed = [];

    /**
     * @param PluginManager $plugins
     */
    public function __construct(CurrentUser $user, PluginManager $plugins)
    {
        $this->plugins = $plugins;
        $this->user = $user->getUser();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ThemeEvent::CONTENT_START => ['renderUnlicensedPlugins', 100],
            ThemeEvent::CONTENT_END => ['renderExpiredPlugins', 100],
        ];
    }

    /**
     * @param ThemeEvent $event
     */
    public function renderUnlicensedPlugins(ThemeEvent $event)
    {
        $unlicensed = [];
        foreach ($this->plugins->getPlugins() as $id => $plugin) {
            if (!$plugin->isLicensed()) {
                $unlicensed[] = $plugin->getName();
            }
        }

        if (count($unlicensed) == 0) {
            return;
        }

        $this->unlicensed = $unlicensed;

        // FIXME translations
        $html = '
            <div class="callout callout-danger">
                <h4>License problems</h4>
                You are running the following plugin(s) without valid license: <strong>' . implode(', ', $unlicensed) . '</strong>. 
                Find out how to buy a (new) license <a href="https://www.kimai.org/store/" target="_blank">at the Kimai marketplace</a>.   
            </div>
        ';

        $event->addContent($html);
    }

    /**
     * @param ThemeEvent $event
     */
    public function renderExpiredPlugins(ThemeEvent $event)
    {
        if (!$this->user->isSuperAdmin()) {
            return;
        }

        $expired = [];
        foreach ($this->plugins->getPlugins() as $id => $plugin) {
            if ($plugin->isExpired() && !in_array($plugin->getName(), $this->unlicensed)) {
                $expired[] = $plugin->getName();
            }
        }

        if (count($expired) == 0) {
            return;
        }

        // FIXME translations
        $html = '
            <div class="callout callout-warning">
                The following plugin(s) have expired: <strong>' . implode(', ', $expired) . '</strong>. Please consider to re-new your license and support further development of open source software.  
            </div>
        ';

        $event->addContent($html);
    }
}

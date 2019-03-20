<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Constants;
use App\Entity\User;
use App\Event\ThemeEvent;
use App\Plugin\PluginManager;
use App\Security\CurrentUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param CurrentUser $user
     * @param PluginManager $plugins
     * @param TranslatorInterface $translator
     */
    public function __construct(CurrentUser $user, PluginManager $plugins, TranslatorInterface $translator)
    {
        $this->plugins = $plugins;
        $this->user = $user->getUser();
        $this->translator = $translator;
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

        $title = $this->translate('title.unlicensed');
        $message = $this->translate('message.unlicensed', [
            '%plugins%' => '<strong>' . implode(', ', $unlicensed) . '</strong>',
            '%marketplace%' => '<a href="https://www.kimai.org/store/" target="_blank">' . $this->translate('plugin.marketplace') . '</a>'
        ]);

        $html = '<div class="callout callout-danger"><h4>' . $title . '</h4>' . $message . '</div>';

        $event->addContent($html);
    }

    /**
     * @param string $key
     * @param array $replacer
     * @return string
     */
    protected function translate(string $key, array $replacer = []): string
    {
        return $this->translator->trans($key, $replacer, 'plugins');
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

        $message = $this->translate('message.expired', [
            '%plugins%' => '<strong>' . implode(', ', $expired) . '</strong>',
            '%marketplace%' => '<a href="' . Constants::HOMEPAGE . '/store/' . '" target="_blank">' . $this->translate('plugin.marketplace') . '</a>'
        ]);

        $html = '<div class="callout callout-warning">' . $message . '</div>';

        $event->addContent($html);
    }
}

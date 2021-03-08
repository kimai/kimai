<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Event\PageActionsEvent;
use App\Event\ThemeEvent;
use App\Event\ThemeJavascriptTranslationsEvent;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class ThemeExtension implements RuntimeExtensionInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @param Environment $environment
     * @param string $eventName
     * @param mixed|null $payload
     * @return ThemeEvent
     */
    public function trigger(Environment $environment, string $eventName, $payload = null): ThemeEvent
    {
        /** @var AppVariable $app */
        $app = $environment->getGlobals()['app'];
        /** @var User $user */
        $user = $app->getUser();

        $themeEvent = new ThemeEvent($user, $payload);

        if ($this->eventDispatcher->hasListeners($eventName)) {
            $this->eventDispatcher->dispatch($themeEvent, $eventName);
        }

        return $themeEvent;
    }

    public function actions(User $user, string $action, array $payload): ThemeEvent
    {
        if (!\array_key_exists('actions', $payload)) {
            $payload['actions'] = [];
        }

        $themeEvent = new PageActionsEvent($user, $payload, $action);

        $eventName = 'actions.' . $action;

        if ($this->eventDispatcher->hasListeners($eventName)) {
            $this->eventDispatcher->dispatch($themeEvent, $eventName);
        }

        return $themeEvent;
    }

    public function getJavascriptTranslations(): array
    {
        $event = new ThemeJavascriptTranslationsEvent();

        $this->eventDispatcher->dispatch($event);

        return $event->getTranslations();
    }
}

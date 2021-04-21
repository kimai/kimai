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

    public function actions(User $user, string $action, string $view, array $payload = []): ThemeEvent
    {
        $themeEvent = new PageActionsEvent($user, $payload, $action, $view);

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

    public function getProgressbarClass(float $percent, ?bool $reverseColors = false): string
    {
        $colors = ['xl' => 'progress-bar-danger', 'l' => 'progress-bar-warning', 'm' => 'progress-bar-success', 's' => 'progress-bar-primary', 'e' => 'progress-bar-info'];
        if (true === $reverseColors) {
            $colors = ['s' => 'progress-bar-danger', 'm' => 'progress-bar-warning', 'l' => 'progress-bar-success', 'xl' => 'progress-bar-primary', 'e' => 'progress-bar-info'];
        }

        if ($percent > 90) {
            $class = $colors['xl'];
        } elseif ($percent > 70) {
            $class = $colors['l'];
        } elseif ($percent > 50) {
            $class = $colors['m'];
        } elseif ($percent > 30) {
            $class = $colors['s'];
        } else {
            $class = $colors['e'];
        }

        return $class;
    }
}

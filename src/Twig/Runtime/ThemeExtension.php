<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Configuration\SystemConfiguration;
use App\Constants;
use App\Entity\User;
use App\Event\PageActionsEvent;
use App\Event\ThemeEvent;
use App\Event\ThemeJavascriptTranslationsEvent;
use App\Utils\Color;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class ThemeExtension implements RuntimeExtensionInterface
{
    private $eventDispatcher;
    private $translator;
    private $configuration;
    /**
     * @var bool
     */
    private $randomColors;

    public function __construct(EventDispatcherInterface $dispatcher, TranslatorInterface $translator, SystemConfiguration $configuration)
    {
        $this->eventDispatcher = $dispatcher;
        $this->translator = $translator;
        $this->configuration = $configuration;
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

    public function generateTitle(?string $prefix = null, string $delimiter = ' – '): string
    {
        $title = $this->configuration->getBrandingTitle();
        if (null === $title || \strlen($title) === 0) {
            $title = Constants::SOFTWARE;
        }

        return ($prefix ?? '') . $title . $delimiter . $this->translator->trans('time_tracking', [], 'messages');
    }

    /**
     * @param string $name
     * @return mixed
     * @deprecated since 1.15
     */
    public function getThemeConfig(string $name)
    {
        @trigger_error('The twig function "theme_config" was deprecated with 1.15, replace it with the global "kimai_config" variable.', E_USER_DEPRECATED);

        switch ($name) {
            case 'auto_reload_datatable':
                @trigger_error('The configuration auto_reload_datatable is deprecated and was removed with 1.4', E_USER_DEPRECATED);

                return false;

            case 'soft_limit':
                return $this->configuration->getTimesheetActiveEntriesHardLimit();

            default:
                $name = 'theme.' . $name;
                break;
        }

        return $this->configuration->find($name);
    }

    public function colorize(?string $color, ?string $identifier = null, ?string $fallback = null): string
    {
        if ($color !== null) {
            return $color;
        }

        if ($this->randomColors === null) {
            $this->randomColors = $this->configuration->isThemeRandomColors();
        }

        if ($this->randomColors) {
            return (new Color())->getRandom($identifier);
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return Constants::DEFAULT_COLOR;
    }
}

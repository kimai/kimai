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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class ThemeExtension implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
        private readonly SystemConfiguration $configuration,
        private readonly Security $security
    )
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function trigger(Environment $environment, string $eventName, array $payload = []): ThemeEvent
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $themeEvent = new ThemeEvent($user, $payload);
        $this->eventDispatcher->dispatch($themeEvent, $eventName);

        return $themeEvent;
    }

    public function actions(User $user, string $action, string $view, array $payload = []): ThemeEvent
    {
        $themeEvent = new PageActionsEvent($user, $payload, $action, $view);
        $this->eventDispatcher->dispatch($themeEvent, $themeEvent->getEventName());

        return $themeEvent;
    }

    public function getJavascriptTranslations(): array
    {
        $event = new ThemeJavascriptTranslationsEvent(); // @phpstan-ignore new.deprecatedClass

        $this->eventDispatcher->dispatch($event);

        $all = [];
        foreach ($event->getTranslations() as $key => $translation) { // @phpstan-ignore method.deprecatedClass
            $all[$key] = $this->translator->trans($translation[0], [], $translation[1]);
        }

        return $all;
    }

    public function getProgressbarClass(float $percent, ?bool $reverseColors = false): string
    {
        $colors = ['xl' => 'bg-red', 'l' => 'bg-warning', 'm' => 'bg-green', 's' => 'bg-green', 'e' => ''];
        if (true === $reverseColors) {
            $colors = ['s' => 'bg-red', 'm' => 'bg-warning', 'l' => 'bg-green', 'xl' => 'bg-green', 'e' => ''];
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

    public function generateTitle(): string
    {
        return Constants::SOFTWARE;
    }

    public function colorize(?string $color, ?string $identifier = null): string
    {
        if ($color !== null) {
            return $color;
        }

        return (new Color())->getRandom($identifier);
    }

    public function getTimePresets(string $timezone): array
    {
        $intervalMinutes = $this->configuration->getTimesheetIncrementMinutes();

        if ($intervalMinutes < 5) {
            return [];
        }

        $maxMinutes = 24 * 60 - $intervalMinutes;

        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $date = $date->setTime(0, 0, 0);

        $presets = [$date];

        for ($minutes = $intervalMinutes; $minutes <= $maxMinutes; $minutes += $intervalMinutes) {
            $date = $date->modify('+' . $intervalMinutes . ' minutes');

            $presets[] = $date;
        }

        return $presets;
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Event\PageActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Base class for all listeners, which adds the pages default toolbars.
 */
abstract class AbstractActionsSubscriber implements EventSubscriberInterface
{
    private ?string $locale = null;

    public function __construct(private readonly AuthorizationCheckerInterface $auth, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function isGranted($attributes, $subject = null): bool
    {
        return $this->auth->isGranted($attributes, $subject);
    }

    protected function path(string $route, array $parameters = []): string
    {
        if ($this->locale !== null) {
            $parameters['_locale'] = $this->locale;
        }

        return $this->urlGenerator->generate($route, $parameters);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'actions.' . static::getActionName() => ['handleEvent', 1000],
        ];
    }

    final public function handleEvent(PageActionsEvent $event): void
    {
        $this->locale = $event->getLocale();

        $this->onActions($event);
    }

    public static function getActionName(): string
    {
        throw new \Exception('You need to overwrite getActionName() or getSubscribedEvents() in ' . static::class);
    }

    public function onActions(PageActionsEvent $event): void
    {
        // non abstract - so the usage is completely optional
    }
}

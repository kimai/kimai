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
    public function __construct(private AuthorizationCheckerInterface $auth, private UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function isGranted($attributes, $subject = null): bool
    {
        return $this->auth->isGranted($attributes, $subject);
    }

    protected function path(string $route, array $parameters = []): string
    {
        return $this->urlGenerator->generate($route, $parameters);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'actions.' . static::getActionName() => ['onActions', 1000],
        ];
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

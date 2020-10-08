<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Event\ThemeEvent;
use App\Security\CurrentUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ThemeEventExtension implements RuntimeExtensionInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var CurrentUser
     */
    private $user;

    public function __construct(EventDispatcherInterface $dispatcher, CurrentUser $user)
    {
        $this->eventDispatcher = $dispatcher;
        $this->user = $user;
    }

    /**
     * @param string $eventName
     * @param mixed|null $payload
     * @return ThemeEvent
     */
    public function trigger(string $eventName, $payload = null): ThemeEvent
    {
        $themeEvent = new ThemeEvent($this->user->getUser(), $payload);

        if ($this->eventDispatcher->hasListeners($eventName)) {
            $this->eventDispatcher->dispatch($themeEvent, $eventName);
        }

        return $themeEvent;
    }
}

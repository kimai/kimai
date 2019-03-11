<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\User;
use App\Event\ThemeEvent;
use App\Security\CurrentUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EventExtensions extends AbstractExtension
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var User
     */
    protected $user;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher, CurrentUser $user)
    {
        $this->eventDispatcher = $dispatcher;
        $this->user = $user->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('trigger', [$this, 'triggerEvent']),
        ];
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param string $eventName
     *
     * @return bool
     */
    protected function hasListener($eventName)
    {
        return $this->getDispatcher()->hasListeners($eventName);
    }

    /**
     * @param string $eventName
     * @param mixed $payload
     * @return ThemeEvent
     */
    public function triggerEvent(string $eventName, $payload = null)
    {
        $themeEvent = new ThemeEvent($this->user, $payload);

        if ($this->hasListener($eventName)) {
            $this->getDispatcher()->dispatch($eventName, $themeEvent);
        }

        return $themeEvent;
    }

}

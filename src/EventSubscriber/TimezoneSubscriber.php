<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TimezoneSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->storage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['setTimezone', 100],
        ];
    }

    public function setTimezone(RequestEvent $event)
    {
        if (!$this->canHandleEvent()) {
            return;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();
        $timezone = $user->getPreferenceValue('timezone', date_default_timezone_get());
        date_default_timezone_set($timezone);
    }

    protected function canHandleEvent(): bool
    {
        if (null === $this->storage->getToken()) {
            return false;
        }

        $user = $this->storage->getToken()->getUser();

        if (null === $user) {
            return false;
        }

        return ($user instanceof User);
    }
}

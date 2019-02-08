<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use KevinPapst\AdminLTEBundle\Event\ShowUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TimezoneSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->storage = $tokenStorage;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['setTimezone', 100],
        ];
    }

    /**
     * @param ShowUserEvent $event
     */
    public function setTimezone(GetResponseEvent $event)
    {
        if (null === $this->storage->getToken()) {
            return;
        }

        /* @var $user User */
        $user = $this->storage->getToken()->getUser();
        $timezone = $user->getPreferenceValue('timezone', date_default_timezone_get());
        date_default_timezone_set($timezone);
    }
}

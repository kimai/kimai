<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\PrepareUserEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserProfileSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param TokenStorageInterface $storage
     */
    public function __construct(EventDispatcherInterface $dispatcher, TokenStorageInterface $storage)
    {
        $this->eventDispatcher = $dispatcher;
        $this->storage = $storage;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['prepareUserProfile', 200]
        ];
    }

    /**
     * @param KernelEvent $event
     */
    public function prepareUserProfile(KernelEvent $event)
    {
        if (!$this->canHandleEvent($event)) {
            return;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        $event = new PrepareUserEvent($user);
        $this->eventDispatcher->dispatch($event, PrepareUserEvent::PREPARE);
    }

    /**
     * @param KernelEvent $event
     * @return bool
     */
    protected function canHandleEvent(KernelEvent $event): bool
    {
        // Ignore sub-requests
        if (!$event->isMasterRequest()) {
            return false;
        }

        // ignore events like the toolbar where we do not have a token
        if (null === $this->storage->getToken()) {
            return false;
        }

        /** @var User $user */
        $user = $this->storage->getToken()->getUser();

        return ($user instanceof User);
    }
}

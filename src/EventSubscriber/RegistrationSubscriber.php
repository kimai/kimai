<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class intercepts the registration to make sure the first-ever
 * registered user will get the SUPER_ADMIN role.
 */
class RegistrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param UserManagerInterface $userManager
     */
    public function __construct(EventDispatcherInterface $dispatcher, UserManagerInterface $userManager)
    {
        $this->eventDispatcher = $dispatcher;
        $this->userManager = $userManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::REGISTRATION_SUCCESS => ['onRegistrationSuccess', 200]
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onRegistrationSuccess(FormEvent $event)
    {
        $users = $this->userManager->findUsers();

        if (!empty($users)) {
            return;
        }

        /** @var $user \FOS\UserBundle\Model\UserInterface */
        $user = $event->getForm()->getData();
        $user->setRoles([User::ROLE_SUPER_ADMIN]);
    }
}

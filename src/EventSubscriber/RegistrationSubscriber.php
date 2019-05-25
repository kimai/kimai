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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class intercepts the registration to make sure the first-ever
 * registered user will get the SUPER_ADMIN role.
 */
class RegistrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    /**
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
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
        /** @var \FOS\UserBundle\Model\UserInterface $user */
        $user = $event->getForm()->getData();
        $roles = [User::ROLE_USER];

        if (empty($this->userManager->findUsers())) {
            $roles = [User::ROLE_SUPER_ADMIN];
        }

        $user->setRoles($roles);
    }
}

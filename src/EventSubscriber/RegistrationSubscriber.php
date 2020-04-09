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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This class intercepts the registration to make sure:
 *
 * - the first-ever registered user will get the SUPER_ADMIN role
 * - the user uses the current request locale as initial language setting
 */
final class RegistrationSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(UserManagerInterface $userManager, UrlGeneratorInterface $router)
    {
        $this->userManager = $userManager;
        $this->router = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::REGISTRATION_SUCCESS => ['onRegistrationSuccess', 200],
            FOSUserEvents::RESETTING_RESET_SUCCESS => ['onResettingSuccess', 200],
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onRegistrationSuccess(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getForm()->getData();
        $roles = [User::ROLE_USER];

        if (empty($this->userManager->findUsers())) {
            $roles = [User::ROLE_SUPER_ADMIN];
        }

        $user->setLanguage($event->getRequest()->getLocale());
        $user->setRoles($roles);
    }

    /**
     * @param FormEvent $event
     */
    public function onResettingSuccess(FormEvent $event)
    {
        $event->setResponse(new RedirectResponse($this->router->generate('my_profile')));
    }
}

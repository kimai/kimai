<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use Avanzu\AdminThemeBundle\Event\ShowUserEvent;
use Avanzu\AdminThemeBundle\Model\UserModel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class NavbarShowUserListener
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class NavbarShowUserListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $storage;

    /**
     * NavbarShowUserListener constructor.
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->storage = $tokenStorage;
    }

    /**
     * @param ShowUserEvent $event
     */
    public function onShowUser(ShowUserEvent $event)
    {
        /* @var $myUser User */
        $myUser = $this->storage->getToken()->getUser();

        $titles = [];
        $roles = $this->storage->getToken()->getRoles();
        foreach ($roles as $role) {
            $titles[] = ucfirst(strtolower(str_replace('ROLE_', '', $role->getRole())));
        }

        $user = new UserModel();
        $user->setName($myUser->getAlias() ?: $myUser->getUsername())
            ->setUsername($myUser->getUsername())
            ->setIsOnline(true)
            ->setTitle($myUser->getTitle())
            ->setAvatar($myUser->getAvatar())
            ->setMemberSince(new \DateTime());

        $event->setUser($user);
    }
}
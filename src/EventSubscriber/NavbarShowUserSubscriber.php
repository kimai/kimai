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
use KevinPapst\AdminLTEBundle\Event\ThemeEvents;
use KevinPapst\AdminLTEBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NavbarShowUserSubscriber implements EventSubscriberInterface
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
            ThemeEvents::THEME_NAVBAR_USER => ['onShowUser', 100],
            ThemeEvents::THEME_SIDEBAR_USER => ['onShowUser', 100],
        ];
    }

    /**
     * @param ShowUserEvent $event
     */
    public function onShowUser(ShowUserEvent $event)
    {
        if (null === $this->storage->getToken()) {
            return;
        }

        /* @var $myUser User */
        $myUser = $this->storage->getToken()->getUser();

        $user = new UserModel();
        $user->setName($myUser->getAlias() ?: $myUser->getUsername())
            ->setUsername($myUser->getUsername())
            ->setIsOnline(true)
            ->setTitle($myUser->getTitle())
            ->setAvatar($myUser->getAvatar())
            ->setMemberSince($myUser->getRegisteredAt());

        $event->setUser($user);
    }
}

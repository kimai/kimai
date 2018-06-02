<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use Avanzu\AdminThemeBundle\Event\ShowUserEvent;
use Avanzu\AdminThemeBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class NavbarShowUserSubscriber
 */
class NavbarShowUserSubscriber implements EventSubscriberInterface
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
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'theme.navbar_user' => ['onShowUser', 100],
            'theme.sidebar_user' => ['onShowUser', 100],
        ];
    }

    /**
     * @param ShowUserEvent $event
     */
    public function onShowUser(ShowUserEvent $event)
    {
        if ($this->storage->getToken() === null) {
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
            ->setMemberSince(new \DateTime());

        $event->setUser($user);
    }
}

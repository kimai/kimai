<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use AppBundle\Event\ConfigureMainMenuEvent;
use AppBundle\Event\ConfigureAdminMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * Class MenuBuilder
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    private $factory;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var AuthorizationChecker
     */
    private $security;

    /**
     * MenuBuilder constructor.
     * @param FactoryInterface $factory
     * @param EventDispatcherInterface $dispatcher
     * @param AuthorizationChecker $security
     */
    public function __construct(FactoryInterface $factory,
        EventDispatcherInterface $dispatcher,
        AuthorizationChecker $security)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $dispatcher;
        $this->security = $security;
    }

    /**
     * Generate the main menu.
     *
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function createMainMenu(array $options)
    {
        $menu = $this->factory->createItem('main');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        $isLoggedIn = $this->security->isGranted('IS_AUTHENTICATED_FULLY');
        $isAdmin = $isLoggedIn && $this->security->isGranted('ROLE_ADMIN');

        if ($isLoggedIn) {
            $item = $menu->addChild('Homepage', array('route' => 'blog_index'));
            $item->setLabel('menu.homepage');
            $item->setChildrenAttribute('icon', 'home');
        }

        $this->eventDispatcher->dispatch(
            ConfigureMainMenuEvent::CONFIGURE,
            new ConfigureMainMenuEvent($this->factory, $menu)
        );

        if ($isAdmin) {
            $item = $menu->addChild('Administration', array('route' => 'admin_post_index'));
            $item->setLabel('menu.admin');
            $item->setChildrenAttribute('icon', 'lock');
        }

        if ($isLoggedIn) {
            $item = $menu->addChild('Logout', array('route' => 'security_logout'));
            $item->setLabel('menu.logout');
            $item->setChildrenAttribute('icon', 'sign-out');
        }

        return $menu;
    }

    /**
     * Generate the admin menu.
     *
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function createAdminMenu(array $options)
    {
        $menu = $this->factory->createItem('admin');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        $isLoggedIn = $this->security->isGranted('IS_AUTHENTICATED_FULLY');
        $isAdmin = $isLoggedIn && $this->security->isGranted('ROLE_ADMIN');

        // FIXME CAN BE REMOVED
        if ($isAdmin) {
            $item = $menu->addChild('Post list', array('route' => 'admin_post_index'));
            $item->setLabel('menu.post_list');
            $item->setChildrenAttribute('icon', 'list-alt');
        }

        $this->eventDispatcher->dispatch(
            ConfigureAdminMenuEvent::CONFIGURE,
            new ConfigureAdminMenuEvent($this->factory, $menu)
        );

        if ($isLoggedIn) {
            $item = $menu->addChild('Logout', array('route' => 'security_logout'));
            $item->setLabel('menu.logout');
            $item->setChildrenAttribute('icon', 'sign-out');
        }

        return $menu;
    }
}

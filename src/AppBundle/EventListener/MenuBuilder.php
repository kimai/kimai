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

use AppBundle\Event\ConfigureMainMenuEvent;
use AppBundle\Event\ConfigureAdminMenuEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;

/**
 * Class MenuBuilder configures the main navigation.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class MenuBuilder
{
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
     * @param EventDispatcherInterface $dispatcher
     * @param AuthorizationChecker $security
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        AuthorizationChecker $security
    )
    {
        $this->eventDispatcher = $dispatcher;
        $this->security = $security;
    }

    /**
     * Generate the main menu.
     *
     * @param SidebarMenuEvent $event
     */
    public function onSetupNavbar(SidebarMenuEvent $event)
    {
        $request = $event->getRequest();
        $isLoggedIn = $this->security->isGranted('IS_AUTHENTICATED_FULLY');
        $isAdmin = $isLoggedIn && $this->security->isGranted('ROLE_ADMIN');

        $event->addItem(
            new MenuItemModel('dashboard', 'menu.homepage', 'dashboard', [], 'fa fa-dashboard') // fa-home
        );

        $this->eventDispatcher->dispatch(
            ConfigureMainMenuEvent::CONFIGURE,
            new ConfigureMainMenuEvent(
                $this->security,
                $request,
                $event
            )
        );

        if ($isAdmin) {
            $admin = new MenuItemModel('admin', 'menu.admin', '', [], 'fa fa-wrench');
            $event->addItem($admin);
            $admin->addChild(
                new MenuItemModel('user_admin', 'menu.admin_user', 'admin_user', [], 'fa fa-user')
            );

            $this->eventDispatcher->dispatch(
                ConfigureAdminMenuEvent::CONFIGURE,
                new ConfigureAdminMenuEvent(
                    $this->security,
                    $request,
                    $event
                )
            );
        }

        $event->addItem(
            new MenuItemModel('logout', 'menu.logout', 'security_logout', [], 'fa fa-sign-out')
        );

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            $event->getItems()
        );

    }

    /**
     * @param string $route
     * @param MenuItemModel[] $items
     */
    protected function activateByRoute($route, $items)
    {
        foreach($items as $item) {
            if($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            }
            else {
                if($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }
            }
        }

    }
}

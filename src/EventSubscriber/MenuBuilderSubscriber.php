<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureAdminMenuEvent;
use App\Event\ConfigureMainMenuEvent;
use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;
use Avanzu\AdminThemeBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class MenuBuilder configures the main navigation.
 */
class MenuBuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $security;

    /**
     * MenuBuilderSubscriber constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param AuthorizationCheckerInterface $security
     */
    public function __construct(EventDispatcherInterface $dispatcher, AuthorizationCheckerInterface $security)
    {
        $this->eventDispatcher = $dispatcher;
        $this->security = $security;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'theme.sidebar_setup_menu' => ['onSetupNavbar', 100],
        ];
    }

    /**
     * Generate the main menu.
     *
     * @param SidebarMenuEvent $event
     */
    public function onSetupNavbar(SidebarMenuEvent $event)
    {
        $request = $event->getRequest();
        $isLoggedIn = $this->security->isGranted('IS_AUTHENTICATED_REMEMBERED');
        $isTeamlead = $isLoggedIn && $this->security->isGranted('ROLE_TEAMLEAD');

        $event->addItem(
            new MenuItemModel('dashboard', 'menu.homepage', 'dashboard', [], 'fas fa-tachometer-alt')
        );

        $this->eventDispatcher->dispatch(
            ConfigureMainMenuEvent::CONFIGURE,
            new ConfigureMainMenuEvent(
                $request,
                $event
            )
        );

        if ($isTeamlead) {
            $admin = new MenuItemModel('admin', 'menu.admin', '', [], 'fas fa-wrench');
            $event->addItem($admin);

            $this->eventDispatcher->dispatch(
                ConfigureAdminMenuEvent::CONFIGURE,
                new ConfigureAdminMenuEvent(
                    $request,
                    $event
                )
            );
        }

        $event->addItem(
            new MenuItemModel('logout', 'menu.logout', 'security_logout', [], 'fas fa-sign-out-alt')
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
        foreach ($items as $item) {
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            } else {
                if ($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }
            }
        }
    }
}

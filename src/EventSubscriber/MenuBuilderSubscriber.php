<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Event\ThemeEvents;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
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
            ThemeEvents::THEME_SIDEBAR_SETUP_MENU => ['onSetupNavbar', 100],
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

        $event->addItem(
            new MenuItemModel('dashboard', 'menu.homepage', 'dashboard', [], 'fas fa-tachometer-alt')
        );

        $menuEvent = new ConfigureMainMenuEvent(
            $request,
            $event,
            new MenuItemModel('admin', 'menu.admin', ''),
            new MenuItemModel('system', 'menu.system', '')
        );

        $this->eventDispatcher->dispatch($menuEvent, ConfigureMainMenuEvent::CONFIGURE);

        if ($menuEvent->getAdminMenu()->hasChildren()) {
            $event->addItem(new MenuItemModel('admin', 'menu.admin', ''));
            foreach ($menuEvent->getAdminMenu()->getChildren() as $child) {
                $event->addItem($child);
            }
        }

        if ($menuEvent->getSystemMenu()->hasChildren()) {
            $event->addItem(new MenuItemModel('system', 'menu.system', ''));
            foreach ($menuEvent->getSystemMenu()->getChildren() as $child) {
                $event->addItem($child);
            }
        }

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

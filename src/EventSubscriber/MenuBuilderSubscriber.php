<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel as KimaiMenuItemModel;
use KevinPapst\AdminLTEBundle\Event\SidebarMenuEvent;
use KevinPapst\AdminLTEBundle\Model\MenuItemInterface;
use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class MenuBuilder configures the main navigation.
 * @internal
 */
class MenuBuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(EventDispatcherInterface $dispatcher, TokenStorageInterface $storage)
    {
        $this->eventDispatcher = $dispatcher;
        $this->tokenStorage = $storage;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SidebarMenuEvent::class => ['onSetupNavbar', 100],
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

        // error pages don't have a user and will fail when is_granted() is called
        if (null !== $this->tokenStorage->getToken()) {
            $this->eventDispatcher->dispatch($menuEvent);
        }

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
     * @param MenuItemInterface[] $items
     */
    protected function activateByRoute($route, $items)
    {
        foreach ($items as $item) {
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            } else {
                if ($item->getRoute() == $route) {
                    $item->setIsActive(true);
                    continue;
                }
                if ($item instanceof KimaiMenuItemModel) {
                    if ($item->isChildRoute($route)) {
                        $item->setIsActive(true);
                        continue;
                    }
                }
            }
        }
    }
}

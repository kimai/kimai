<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use KevinPapst\TablerBundle\Event\MenuEvent;
use KevinPapst\TablerBundle\Model\MenuItemInterface;
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
            MenuEvent::class => ['onSetupNavbar', 100],
        ];
    }

    public function onSetupNavbar(MenuEvent $event): void
    {
        $request = $event->getRequest();

        $menuEvent = new ConfigureMainMenuEvent(
            $request,
            new MenuItemModel('main', 'menu.root'),
            new MenuItemModel('admin', 'menu.admin', '', [], 'administration'),
            new MenuItemModel('system', 'menu.system', '', [], 'configuration')
        );

        // error pages don't have a user and will fail when is_granted() is called
        if (null !== $this->tokenStorage->getToken()) {
            $this->eventDispatcher->dispatch($menuEvent);
        }

        foreach ($menuEvent->getMenu()->getChildren() as $child) {
            if ($child->getRoute() === null && !$child->hasChildren()) {
                continue;
            }
            $event->addItem($child);
        }

        if ($menuEvent->getAdminMenu()->hasChildren()) {
            $event->addItem($menuEvent->getAdminMenu());
        }
        if ($menuEvent->getSystemMenu()->hasChildren()) {
            $event->addItem($menuEvent->getSystemMenu());
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
                if ($item instanceof MenuItemModel) {
                    if ($item->isChildRoute($route)) {
                        $item->setIsActive(true);
                        continue;
                    }
                }
            }
        }
    }
}

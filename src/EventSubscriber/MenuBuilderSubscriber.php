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
use Symfony\Component\Security\Core\Security;

/**
 * Class MenuBuilder configures the main navigation.
 * @internal
 */
class MenuBuilderSubscriber implements EventSubscriberInterface
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MenuEvent::class => ['onSetupNavbar', 100],
        ];
    }

    public function onSetupNavbar(MenuEvent $event): void
    {
        $menuEvent = new ConfigureMainMenuEvent();

        // error pages don't have a user and will fail when is_granted() is called
        if (null !== ($user = $this->security->getUser())) {
            $this->eventDispatcher->dispatch($menuEvent);
        }

        foreach ($menuEvent->getMenu()->getChildren() as $child) {
            if ($child->getRoute() === null && !$child->hasChildren()) {
                continue;
            }
            $event->addItem($child);
        }

        if ($menuEvent->getAppsMenu()->hasChildren()) {
            $event->addItem($menuEvent->getAppsMenu());
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
    protected function activateByRoute(string $route, array $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof MenuItemModel) {
                if ($item->isChildRoute($route)) {
                    $item->setIsActive(true);
                    continue;
                }
            }

            if ($item->getRoute() === $route) {
                $item->setIsActive(true);
                continue;
            }

            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            }
        }
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MenuFavoritesSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMainMenuConfigure', 90], // see MenuSubscriber
        ];
    }

    public function onMainMenuConfigure(ConfigureMainMenuEvent $menuEvent): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $favMenu = $menuEvent->getMenu()->findChild('favorites');
        if ($favMenu === null) {
            return;
        }

        $userFavorites = $user->getPreferenceValue('favorite_routes');
        if (!\is_string($userFavorites) || trim($userFavorites) === '') {
            return;
        }

        $root = new MenuItemModel('', '');
        $root->addChild($menuEvent->getMenu());

        if ($menuEvent->getAppsMenu()->hasChildren()) {
            $root->addChild($menuEvent->getAppsMenu());
        }
        if ($menuEvent->getAdminMenu()->hasChildren()) {
            $root->addChild($menuEvent->getAdminMenu());
        }
        if ($menuEvent->getSystemMenu()->hasChildren()) {
            $root->addChild($menuEvent->getSystemMenu());
        }

        $userFavorites = explode(',', $userFavorites);
        foreach ($userFavorites as $fav) {
            $tmp = $root->findChild($fav);
            if ($tmp !== null && !$tmp->hasChildren()) {
                $favMenu->addChild(clone $tmp);
            }
        }

        if ($favMenu->hasChildren()) {
            $favMenu->setExpanded(true);
            $menuEvent->getTimesheetMenu()?->setExpanded(false);
        }
    }
}

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
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $userFavorites = $user->getPreferenceValue('favorite_routes');
        if (!\is_string($userFavorites) || trim($userFavorites) === '') {
            return;
        }

        $favMenu = $menuEvent->findById('favorites');
        if ($favMenu === null) {
            return;
        }

        $userFavorites = explode(',', $userFavorites);
        foreach ($userFavorites as $fav) {
            $tmp = $menuEvent->findById($fav);
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

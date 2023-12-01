<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\User;
use App\Event\ConfigureMainMenuEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class MenuService
{
    private ?ConfigureMainMenuEvent $menuEvent = null;

    public function __construct(private EventDispatcherInterface $eventDispatcher, private Security $security)
    {
    }

    public function getKimaiMenu(): ConfigureMainMenuEvent
    {
        if (null === $this->menuEvent) {
            $this->menuEvent = new ConfigureMainMenuEvent();
            /** @var User $user */
            $user = $this->security->getUser();

            // error pages don't have a user and will fail when is_granted() is called
            if (null !== $user) {
                $this->eventDispatcher->dispatch($this->menuEvent);
            }
        }

        return $this->menuEvent;
    }
}

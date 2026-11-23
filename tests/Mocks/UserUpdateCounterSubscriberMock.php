<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Event\UserUpdatePostEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Counts how often a user was saved through the UserService.
 */
final class UserUpdateCounterSubscriberMock implements EventSubscriberInterface
{
    private int $count = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            UserUpdatePostEvent::class => ['onUserUpdate', 100],
        ];
    }

    public function onUserUpdate(UserUpdatePostEvent $event): void
    {
        $this->count++;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}

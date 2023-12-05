<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * To be used when a user profile is loaded and should be filled with dynamic user preferences.
 *
 * @internal
 */
final class PrepareUserEvent extends Event
{
    public function __construct(private User $user, private bool $booting = true)
    {
    }

    /**
     * Whether this event is dispatched for the currently logged in user during kernel boot.
     */
    public function isBooting(): bool
    {
        return $this->booting;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}

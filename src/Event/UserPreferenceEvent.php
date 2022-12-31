<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use App\Entity\UserPreference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event should be used, if further user preferences should be added dynamically.
 */
final class UserPreferenceEvent extends Event
{
    /**
     * @param User $user
     * @param UserPreference[] $preferences
     */
    public function __construct(private User $user, private array $preferences)
    {
    }

    /**
     * Do not set the preferences directly to the user object, but ONLY via addPreference()
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return UserPreference[]
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function addPreference(UserPreference $preference): void
    {
        foreach ($this->preferences as $pref) {
            if (strtolower($pref->getName()) === strtolower($preference->getName())) {
                throw new \InvalidArgumentException(
                    'Cannot add user preference, one with the name "' . $preference->getName() . '" is already existing'
                );
            }
        }
        $this->preferences[] = $preference;
    }
}

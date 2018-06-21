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
use Symfony\Component\EventDispatcher\Event;

/**
 * Class UserPreferenceEvent
 */
class UserPreferenceEvent extends Event
{
    const CONFIGURE = 'app.user_preferences';

    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserPreference[]
     */
    protected $preferences;

    /**
     * UserPreferenceEvent constructor.
     * @param User $user
     * @param UserPreference[] $preferences
     */
    public function __construct(User $user, array $preferences)
    {
        $this->user = $user;
        $this->preferences = $preferences;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return UserPreference[]
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * @param UserPreference $preference
     */
    public function addUserPreference(UserPreference $preference)
    {
        foreach ($this->preferences as $pref) {
            if ($pref->getName() == $preference->getName()) {
                throw new \InvalidArgumentException(
                    'Cannot add preference, one with the name "' . $preference->getName() . '" is already existing'
                );
            }
        }
        $this->preferences[] = $preference;
    }
}

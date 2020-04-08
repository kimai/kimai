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
 * This event should be used, if further user preferences should added dynamically
 */
final class UserPreferenceEvent extends Event
{
    /**
     * @deprecated since 1.4, will be removed with 2.0
     */
    public const CONFIGURE = UserPreferenceEvent::class;

    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserPreference[]
     */
    protected $preferences;

    /**
     * @param User $user
     * @param UserPreference[] $preferences
     */
    public function __construct(User $user, array $preferences)
    {
        $this->user = $user;
        $this->preferences = $preferences;
    }

    /**
     * Do not set the preferences directly to the user object, but ONLY via addPreference()
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
    public function addPreference(UserPreference $preference)
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

    /**
     * @param UserPreference $preference
     * @deprecated since 1.4, will be removed with 2.0
     */
    public function addUserPreference(UserPreference $preference)
    {
        @trigger_error('addUserPreference() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
        $this->addPreference($preference);
    }
}

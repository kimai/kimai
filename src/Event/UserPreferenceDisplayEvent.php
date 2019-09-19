<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\UserPreference;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dynamically find possible user preferences to display.
 */
final class UserPreferenceDisplayEvent extends Event
{
    public const EXPORT = 'export';
    public const USERS = 'users';

    /**
     * @var UserPreference[]
     */
    protected $preferences = [];
    /**
     * @var string
     */
    private $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return UserPreference[]
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function addPreference(UserPreference $preference)
    {
        $this->preferences[] = $preference;
    }

    /**
     * If you want to filter where the preference will be displayed, check the current location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }
}

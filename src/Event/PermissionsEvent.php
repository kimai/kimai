<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event can be used, to dynamically change the displayed permissions in the permission screen.
 */
final class PermissionsEvent extends Event
{
    /**
     * @var array
     */
    private $sections = [];

    /**
     * @param string $section
     * @param string[] $permissions
     * @return PermissionsEvent
     */
    public function addPermissions(string $section, array $permissions): PermissionsEvent
    {
        $this->sections[$section] = $permissions;

        return $this;
    }

    public function removePermission(string $section, string $permission): PermissionsEvent
    {
        if (array_key_exists($section, $this->sections)) {
            if (array_key_exists($permission, $this->sections[$section])) {
                unset($this->sections[$section][$permission]);
            }
        }

        return $this;
    }

    public function hasSection(string $section): bool
    {
        return array_key_exists($section, $this->sections);
    }

    public function removeSection(string $section): PermissionsEvent
    {
        if (array_key_exists($section, $this->sections)) {
            unset($this->sections[$section]);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->sections;
    }
}

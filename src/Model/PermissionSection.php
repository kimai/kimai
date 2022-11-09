<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class PermissionSection implements PermissionSectionInterface
{
    public function __construct(private string $title, private string $filter)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function filter(string $permission): bool
    {
        return str_contains($permission, $this->filter);
    }
}

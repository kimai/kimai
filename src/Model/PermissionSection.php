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
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $filter;

    public function __construct(string $title, string $strposFilter)
    {
        $this->title = $title;
        $this->filter = $strposFilter;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function filter(string $permission): bool
    {
        return strpos($permission, $this->filter) !== false;
    }
}

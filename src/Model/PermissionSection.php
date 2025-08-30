<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * @final with 3.0
 */
class PermissionSection implements PermissionSectionInterface // @phpstan-ignore class.implementsDeprecatedInterface
{
    /** @var array<string> */
    private array $filter;

    /**
     * @param string|array<string> $filter
     */
    public function __construct(private readonly string $title, string|array $filter)
    {
        if (!\is_array($filter)) {
            $filter = [$filter];
        }
        $this->filter = $filter;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function filter(string $permission): bool
    {
        foreach ($this->filter as $filter) {
            if (str_contains($permission, $filter)) {
                return true;
            }
        }

        return false;
    }
}

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
 * Allows plugins to exclude specific entries from the ticktack (active timesheets toolbar).
 *
 * Each rule is an associative array matching entry properties by entity ID,
 * e.g. ['project' => 5, 'activity' => 3].
 * An entry is excluded when ALL keys in a rule match (entry.{key}.id === value).
 * Multiple rules are combined with OR (any matching rule excludes the entry).
 */
final class TicktackExcludeEvent extends Event
{
    /** @var array<array<string, int>> */
    private array $excludes = [];

    /**
     * @param array<string, int> $rule e.g. ['project' => 5, 'activity' => 3]
     */
    public function addExclude(array $rule): void
    {
        $this->excludes[] = $rule;
    }

    /**
     * @return array<array<string, int>>
     */
    public function getExcludes(): array
    {
        return $this->excludes;
    }
}

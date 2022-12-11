<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User;

use App\Repository\TeamRepository;

final class TeamService
{
    /**
     * @var array<string, int>
     */
    private array $cache = [];

    public function __construct(private TeamRepository $repository)
    {
    }

    public function countTeams(): int
    {
        if (!\array_key_exists('count', $this->cache)) {
            $this->cache['count'] = $this->repository->count([]);
        }

        return $this->cache['count'];
    }

    public function hasTeams(): bool
    {
        return $this->countTeams() > 0;
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Same as WorkingTimeQueryStatsEvent, but for a query that loads the statistics of several users at once.
 */
final class WorkingTimeQueryUsersStatsEvent extends Event
{
    /**
     * @param array<User> $users
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $users,
        private readonly \DateTimeInterface $begin,
        private readonly \DateTimeInterface $end
    )
    {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @return array<User>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function getBegin(): \DateTimeInterface
    {
        return $this->begin;
    }

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }
}

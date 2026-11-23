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

final class WorkingTimeQueryStatsEvent extends Event
{
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly User $user,
        private readonly \DateTimeInterface $begin,
        private readonly \DateTimeInterface $end
    )
    {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getUser(): User
    {
        return $this->user;
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

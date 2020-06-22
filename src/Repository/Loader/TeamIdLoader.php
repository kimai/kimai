<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class TeamIdLoader implements LoaderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int[] $ids
     */
    public function loadResults(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL t.{id}', 'users')
            ->from(Team::class, 't')
            ->leftJoin('t.users', 'users')
            ->andWhere($qb->expr()->in('t.id', $ids))
            ->getQuery()
            ->execute();
    }
}

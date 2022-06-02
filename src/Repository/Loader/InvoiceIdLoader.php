<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Invoice;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
final class InvoiceIdLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param int[] $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL i.{id}', 'customer')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.customer', 'customer')
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL i.{id}', 'user')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.user', 'user')
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL i.{id}', 'meta')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.meta', 'meta')
            ->andWhere($qb->expr()->in('i.id', $results))
            ->getQuery()
            ->execute();
    }
}

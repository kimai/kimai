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
        $customer = $qb->select('PARTIAL i.{id}', 'customer')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.customer', 'customer')
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $user = $qb->select('PARTIAL i.{id}', 'user')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.user', 'user')
            ->getQuery()
            ->execute();
    }
}

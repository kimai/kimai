<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

use App\Entity\Customer;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerLoader implements LoaderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private bool $fullyHydrated = false)
    {
    }

    /**
     * @param array<int|Customer> $results
     */
    public function loadResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $ids = array_map(function ($customer) {
            if ($customer instanceof Customer) {
                return $customer->getId();
            }

            return $customer;
        }, $results);

        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();
        /** @var Customer[] $customers */
        $customers = $qb->select('PARTIAL c.{id}', 'meta')
            ->from(Customer::class, 'c')
            ->leftJoin('c.meta', 'meta')
            ->andWhere($qb->expr()->in('c.id', $ids))
            ->getQuery()
            ->execute();

        $qb = $em->createQueryBuilder();
        $qb->select('PARTIAL c.{id}', 'teams')
            ->from(Customer::class, 'c')
            ->leftJoin('c.teams', 'teams')
            ->andWhere($qb->expr()->in('c.id', $ids))
            ->getQuery()
            ->execute();

        // do not load team members or leads by default, because they will only be used on detail pages
        // and there is no benefit in adding multiple queries for most requests when they are only needed in one place
        if ($this->fullyHydrated) {
            $teamIds = [];
            foreach ($customers as $customer) {
                foreach ($customer->getTeams() as $team) {
                    $teamIds[] = $team->getId();
                }
            }
            $teamIds = array_unique($teamIds);

            if (\count($teamIds) > 0) {
                $qb = $em->createQueryBuilder();
                $qb->select('PARTIAL team.{id}', 'members', 'user')
                    ->from(Team::class, 'team')
                    ->leftJoin('team.members', 'members')
                    ->leftJoin('members.user', 'user')
                    ->andWhere($qb->expr()->in('team.id', $teamIds))
                    ->getQuery()
                    ->execute();
            }
        }
    }
}

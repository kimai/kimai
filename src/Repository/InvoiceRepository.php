<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Loader\InvoiceLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\InvoiceQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * @extends \Doctrine\ORM\EntityRepository<Invoice>
 */
class InvoiceRepository extends EntityRepository
{
    public function saveInvoice(Invoice $invoice)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($invoice);
        $entityManager->flush();
    }

    public function deleteInvoice(Invoice $invoice)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($invoice);
        $entityManager->flush();
    }

    private function getCounterFor(\DateTime $start, \DateTime $end, ?Customer $customer = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(i.createdAt) as counter')
            ->from(Invoice::class, 'i')
            ->andWhere($qb->expr()->gte('i.createdAt', ':start'))
            ->andWhere($qb->expr()->lte('i.createdAt', ':end'))
            ->setParameter('start', $start)
            ->setParameter('end', $end)
        ;

        if (null !== $customer) {
            $qb
                ->andWhere($qb->expr()->eq('i.customer', ':customer'))
                ->setParameter('customer', $customer)
            ;
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        if ($result === null) {
            return 0;
        }

        return $result['counter'];
    }

    public function getCounterForDay(\DateTime $date, ?Customer $customer = null): int
    {
        $start = (clone $date)->setTime(0, 0, 0);
        $end = (clone $date)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer);
    }

    public function getCounterForMonth(\DateTime $date, ?Customer $customer = null): int
    {
        $start = (clone $date)->setDate((int) $date->format('Y'), (int) $date->format('n'), 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('t'))->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer);
    }

    public function getCounterForYear(\DateTime $date, ?Customer $customer = null): int
    {
        $start = (clone $date)->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate((int) $date->format('Y'), 12, 31)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer);
    }

    public function getCounterForAllTime(\DateTime $date, ?Customer $customer = null): int
    {
        if (null !== $customer) {
            return $this->count(['customer' => $customer]);
        }

        return $this->count([]);
    }

    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [])
    {
        // make sure that all queries without a user see all projects
        if (null === $user && empty($teams)) {
            return;
        }

        // make sure that admins see all projects
        if (null !== $user && $user->canSeeAllData()) {
            return;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams()->toArray());
        }

        $qb->leftJoin('i.customer', 'c');

        if (empty($teams)) {
            $qb->andWhere('SIZE(c.teams) = 0');

            return;
        }

        $orCustomer = $qb->expr()->orX(
            'SIZE(c.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'c.teams')
        );
        $qb->andWhere($orCustomer);

        $ids = array_values(array_unique(array_map(function (Team $team) {
            return $team->getId();
        }, $teams)));

        $qb->setParameter('teams', $ids);
    }

    private function getQueryBuilderForQuery(InvoiceQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('i')
            ->from(Invoice::class, 'i')
        ;

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            case 'date':
                $orderBy = 'i.createdAt';
                break;
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        $this->addPermissionCriteria($qb, $query->getCurrentUser());

        return $qb;
    }

    public function countInvoicesForQuery(InvoiceQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($qb->expr()->countDistinct('i.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    protected function getPaginatorForQuery(InvoiceQuery $query): PaginatorInterface
    {
        $counter = $this->countInvoicesForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new InvoiceLoader($qb->getEntityManager()), $qb, $counter);
    }

    public function getPagerfantaForQuery(InvoiceQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }
}

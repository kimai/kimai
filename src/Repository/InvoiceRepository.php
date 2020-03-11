<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Invoice;
use App\Repository\Loader\InvoiceLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\InvoiceQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class InvoiceRepository extends EntityRepository
{
    public function saveInvoice(Invoice $invoice)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($invoice);
        $entityManager->flush();
    }

    public function getPendingInvoices(): array
    {
        return $this->findBy(['status' => Invoice::STATUS_PENDING]);
    }

    public function getNewInvoices(): array
    {
        return $this->findBy(['status' => Invoice::STATUS_NEW]);
    }

    public function getAllInvoices(): array
    {
        return $this->findBy([], 'createdAt');
    }

    private function getCounterFor(\DateTime $start, \DateTime $end): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(i.createdAt) as counter')
            ->from(Invoice::class, 'i')
            ->andWhere($qb->expr()->gte('i.createdAt', ':start'))
            ->andWhere($qb->expr()->lte('i.createdAt', ':end'))
            ->setParameter('start', $start)
            ->setParameter('end', $end)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();

        if ($result === null) {
            return 0;
        }

        return $result['counter'];
    }

    public function getCounterForDay(\DateTime $date): int
    {
        $start = (clone $date)->setTime(0, 0, 0);
        $end = (clone $date)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end);
    }

    public function getCounterForMonth(\DateTime $date): int
    {
        $start = (clone $date)->setDate($date->format('Y'), $date->format('n'), 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate($date->format('Y'), $date->format('n'), $date->format('t'))->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end);
    }

    public function getCounterForYear(\DateTime $date): int
    {
        $start = (clone $date)->setDate($date->format('Y'), 1, 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate($date->format('Y'), 12, 31)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end);
    }

    public function getCounterForAllTime(\DateTime $date): int
    {
        return $this->count([]);
    }

    private function getQueryBuilderForQuery(InvoiceQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('i')
            ->from(Invoice::class, 'i')
            ->leftJoin('i.customer', 'c')
        ;

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            case 'date':
                $orderBy = 'i.createdAt';
                break;

            case 'customer':
                $orderBy = 'c.name';
                break;

            case 'status':
                $orderBy = 'i.status';
                break;

            case 'total_rate':
                $orderBy = 'i.total';
                break;
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        // this will make sure, that we do not accidentally create results with multiple rows
        //   => which would result in a wrong LIMIT / pagination results
        // the second group by is needed due to SQL standard (even though logically not really required for this query)
        $qb->addGroupBy('i.id')->addGroupBy($orderBy);

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

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
use App\Entity\InvoiceMeta;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Loader\InvoiceLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\InvoiceArchiveQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends \Doctrine\ORM\EntityRepository<Invoice>
 */
class InvoiceRepository extends EntityRepository
{
    use RepositorySearchTrait;

    public function saveInvoice(Invoice $invoice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($invoice);
        $entityManager->flush();
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($invoice);
        $entityManager->flush();
    }

    public function hasInvoice(string $invoiceNumber): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(i.id) as counter')
            ->from(Invoice::class, 'i')
            ->andWhere($qb->expr()->eq('i.invoiceNumber', ':number'))
            ->setParameter('number', $invoiceNumber)
        ;

        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        return $counter > 0;
    }

    private function getCounterFor(\DateTime $start, \DateTime $end, ?Customer $customer = null, ?User $user = null): int
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
                ->setParameter('customer', $customer->getId())
            ;
        }

        if (null !== $user) {
            $qb
                ->andWhere($qb->expr()->eq('i.user', ':user'))
                ->setParameter('user', $user->getId())
            ;
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        if ($result === null) {
            return 0;
        }

        return $result['counter'];
    }

    public function getCounterForDay(\DateTimeInterface $date, ?Customer $customer = null, ?User $user = null): int
    {
        $date = \DateTime::createFromInterface($date);
        $start = (clone $date)->setTime(0, 0, 0);
        $end = (clone $date)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer, $user);
    }

    public function getCounterForMonth(\DateTimeInterface $date, ?Customer $customer = null, ?User $user = null): int
    {
        $date = \DateTime::createFromInterface($date);
        $start = (clone $date)->setDate((int) $date->format('Y'), (int) $date->format('n'), 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('t'))->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer, $user);
    }

    public function getCounterForYear(\DateTimeInterface $date, ?Customer $customer = null, ?User $user = null): int
    {
        $date = \DateTime::createFromInterface($date);
        $start = (clone $date)->setDate((int) $date->format('Y'), 1, 1)->setTime(0, 0, 0);
        $end = (clone $date)->setDate((int) $date->format('Y'), 12, 31)->setTime(23, 59, 59);

        return $this->getCounterFor($start, $end, $customer, $user);
    }

    public function getCounterForCustomerAllTime(?Customer $customer = null): int
    {
        if (null !== $customer) {
            return $this->count(['customer' => $customer->getId()]);
        }

        return $this->count([]);
    }

    public function getCounterForUserAllTime(?User $user = null): int
    {
        if (null !== $user) {
            return $this->count(['user' => $user->getId()]);
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
            $teams = array_merge($teams, $user->getTeams());
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

    private function getQueryBuilderForQuery(InvoiceArchiveQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('i')
            ->from(Invoice::class, 'i')
        ;

        if ($query->getBegin() !== null) {
            $qb->andWhere($qb->expr()->gte('i.createdAt', ':begin'));
            $qb->setParameter('begin', $query->getBegin());
        }

        if ($query->getEnd() !== null) {
            $qb->andWhere($qb->expr()->lte('i.createdAt', ':end'));
            $qb->setParameter('end', $query->getEnd());
        }

        if ($query->hasCustomers()) {
            $qb->andWhere($qb->expr()->in('i.customer', ':customer'));
            $qb->setParameter('customer', $query->getCustomers());
        }

        if ($query->hasStatus()) {
            $qb->andWhere($qb->expr()->in('i.status', ':status'));
            $qb->setParameter('status', $query->getStatus());
        }

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            case 'date':
                $orderBy = 'i.createdAt';
                break;
            case 'invoice.number':
                $orderBy = 'i.invoiceNumber';
                break;
            case 'payed':
                $orderBy = 'i.paymentDate';
                break;
            case 'total_rate':
                $orderBy = 'i.total';
                break;
            case 'status':
                $orderBy = 'i.status';
                break;
            case 'tax':
                $orderBy = 'i.tax';
                break;
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        $this->addPermissionCriteria($qb, $query->getCurrentUser());

        if ($query->hasSearchTerm()) {
            $qb->leftJoin('i.customer', 'customer');
            $this->addSearchTerm($qb, $query);
        }

        return $qb;
    }

    private function getMetaFieldClass(): string
    {
        return InvoiceMeta::class;
    }

    private function getMetaFieldName(): string
    {
        return 'invoice';
    }

    /**
     * @return array<string>
     */
    private function getSearchableFields(): array
    {
        return ['i.comment', 'customer.name', 'customer.company'];
    }

    public function countInvoicesForQuery(InvoiceArchiveQuery $query): int
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

    /**
     * @param InvoiceArchiveQuery $query
     * @return Invoice[]
     */
    public function getInvoicesForQuery(InvoiceArchiveQuery $query): iterable
    {
        // this is using the paginator internally, as it will load all joined entities into the working unit
        // do not "optimize" to use the query directly, as it would results in hundreds of additional lazy queries
        $paginator = $this->getPaginatorForQuery($query);

        return $paginator->getAll();
    }

    protected function getPaginatorForQuery(InvoiceArchiveQuery $query): PaginatorInterface
    {
        $counter = $this->countInvoicesForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new InvoiceLoader($qb->getEntityManager()), $qb, $counter);
    }

    public function getPagerfantaForQuery(InvoiceArchiveQuery $query): Pagination
    {
        return new Pagination($this->getPaginatorForQuery($query), $query);
    }
}

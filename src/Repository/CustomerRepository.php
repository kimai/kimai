<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\CustomerComment;
use App\Entity\Project;
use App\Entity\Team;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\CustomerStatistic;
use App\Repository\Loader\CustomerLoader;
use App\Repository\Paginator\LoaderPaginator;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\CustomerQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

/**
 * @extends \Doctrine\ORM\EntityRepository<Customer>
 */
class CustomerRepository extends EntityRepository
{
    /**
     * @param mixed $id
     * @param null $lockMode
     * @param null $lockVersion
     * @return Customer|null
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = parent::find($id, $lockMode, $lockVersion);
        if (null === $customer) {
            return null;
        }

        $loader = new CustomerLoader($this->getEntityManager(), true);
        $loader->loadResults([$customer]);

        return $customer;
    }

    /**
     * @param Customer $customer
     * @throws ORMException
     */
    public function saveCustomer(Customer $customer): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($customer);
        $entityManager->flush();
    }

    public function countCustomer(bool $visible = false): int
    {
        if ($visible) {
            return $this->count(['visible' => (bool) $visible]);
        }

        return $this->count([]);
    }

    /**
     * @deprecated since 1.15 use CustomerStatisticService::getCustomerStatistics() instead - will be removed with 2.0
     * @codeCoverageIgnore
     *
     * @param Customer $customer
     * @return CustomerStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCustomerStatistics(Customer $customer): CustomerStatistic
    {
        $stats = new CustomerStatistic();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->from(Timesheet::class, 't')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
            ->addSelect('COUNT(t.id) as amount')
            ->addSelect('t.billable as billable')
            ->addSelect('COALESCE(SUM(t.duration), 0) as duration')
            ->addSelect('COALESCE(SUM(t.rate), 0) as rate')
            ->addSelect('COALESCE(SUM(t.internalRate), 0) as internal_rate')
            ->andWhere('p.customer = :customer')
            ->setParameter('customer', $customer)
            ->groupBy('billable')
        ;

        $timesheetResult = $qb->getQuery()->getResult();

        if (null !== $timesheetResult) {
            $amount = 0;
            $duration = 0;
            $rate = 0.00;
            $rateInternal = 0.00;
            foreach ($timesheetResult as $resultRow) {
                $amount += $resultRow['amount'];
                $duration += $resultRow['duration'];
                $rate += $resultRow['rate'];
                $rateInternal += $resultRow['internal_rate'];
                if ($resultRow['billable']) {
                    $stats->setDurationBillable($resultRow['duration']);
                    $stats->setRateBillable($resultRow['rate']);
                    $stats->setRecordAmountBillable($resultRow['amount']);
                }
            }
            $stats->setCounter($amount);
            $stats->setRecordDuration($duration);
            $stats->setRecordRate($rate);
            $stats->setRecordInternalRate($rateInternal);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('COUNT(a.id) as amount')
            ->from(Activity::class, 'a')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 'a.project = p.id')
            ->andWhere('a.project = p.id')
            ->andWhere('p.customer = :customer')
            ->setParameter('customer', $customer)
        ;

        $activityResult = $qb->getQuery()->getOneOrNullResult();

        if (null !== $activityResult) {
            $stats->setActivityAmount($activityResult['amount']);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(p.id) as amount')
            ->from(Project::class, 'p')
            ->andWhere('p.customer = :customer')
            ->setParameter('customer', $customer)
        ;

        $projectResult = $qb->getQuery()->getOneOrNullResult();

        if (null !== $projectResult) {
            $stats->setProjectAmount($projectResult['amount']);
        }

        return $stats;
    }

    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [])
    {
        $permissions = $this->getPermissionCriteria($qb, $user, $teams);
        if ($permissions->count() > 0) {
            $qb->andWhere($permissions);
        }
    }

    private function getPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = []): Andx
    {
        $andX = $qb->expr()->andX();

        // make sure that all queries without a user see all customers
        if (null === $user && empty($teams)) {
            return $andX;
        }

        // make sure that admins see all customers
        if (null !== $user && $user->canSeeAllData()) {
            return $andX;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams());
        }

        if (empty($teams)) {
            $andX->add('SIZE(c.teams) = 0');

            return $andX;
        }

        $or = $qb->expr()->orX(
            'SIZE(c.teams) = 0',
            $qb->expr()->isMemberOf(':teams', 'c.teams')
        );
        $andX->add($or);

        $ids = array_values(array_unique(array_map(function (Team $team) {
            return $team->getId();
        }, $teams)));

        $qb->setParameter('teams', $ids);

        return $andX;
    }

    /**
     * @deprecated since 1.1 - use getQueryBuilderForFormType() instead - will be removed with 2.0
     * @codeCoverageIgnore
     */
    public function builderForEntityType($customer)
    {
        $query = new CustomerFormTypeQuery();
        $query->addCustomer($customer);

        return $this->getQueryBuilderForFormType($query);
    }

    /**
     * Returns a query builder that is used for CustomerType and your own 'query_builder' option.
     *
     * @param CustomerFormTypeQuery $query
     * @return QueryBuilder
     */
    public function getQueryBuilderForFormType(CustomerFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Customer::class, 'c')
            ->orderBy('c.name', 'ASC');

        $mainQuery = $qb->expr()->andX();

        $mainQuery->add($qb->expr()->eq('c.visible', ':visible'));
        $qb->setParameter('visible', true, \PDO::PARAM_BOOL);

        $permissions = $this->getPermissionCriteria($qb, $query->getUser(), $query->getTeams());
        if ($permissions->count() > 0) {
            $mainQuery->add($permissions);
        }

        $outerQuery = $qb->expr()->orX();

        // this is a risk, as a user can manipulate the query and inject IDs that would be hidden otherwise
        if ($query->isAllowCustomerPreselect() && $query->hasCustomers()) {
            $outerQuery->add($qb->expr()->in('c.id', ':customer'));
            $qb->setParameter('customer', $query->getCustomers());
        }

        if (null !== $query->getCustomerToIgnore()) {
            $mainQuery = $qb->expr()->andX(
                $mainQuery,
                $qb->expr()->neq('c.id', ':ignored')
            );
            $qb->setParameter('ignored', $query->getCustomerToIgnore());
        }

        $outerQuery->add($mainQuery);
        $qb->andWhere($outerQuery);

        return $qb;
    }

    private function getQueryBuilderForQuery(CustomerQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('c')
            ->from(Customer::class, 'c')
        ;

        foreach ($query->getOrderGroups() as $orderBy => $order) {
            switch ($orderBy) {
                case 'vat_id':
                    $orderBy = 'c.vatId';
                    break;
                default:
                    $orderBy = 'c.' . $orderBy;
                    break;
            }
            $qb->addOrderBy($orderBy, $order);
        }

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', true, \PDO::PARAM_BOOL);
        } elseif ($query->isShowHidden()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', false, \PDO::PARAM_BOOL);
        }

        $this->addPermissionCriteria($qb, $query->getCurrentUser(), $query->getTeams());

        if ($query->hasSearchTerm()) {
            $searchAnd = $qb->expr()->andX();
            $searchTerm = $query->getSearchTerm();

            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $qb->leftJoin('c.meta', 'meta');
                $searchAnd->add(
                    $qb->expr()->andX(
                        $qb->expr()->eq('meta.name', ':metaName'),
                        $qb->expr()->like('meta.value', ':metaValue')
                    )
                );
                $qb->setParameter('metaName', $metaName);
                $qb->setParameter('metaValue', '%' . $metaValue . '%');
            }

            if ($searchTerm->hasSearchTerm()) {
                $searchAnd->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('c.name', ':searchTerm'),
                        $qb->expr()->like('c.comment', ':searchTerm'),
                        $qb->expr()->like('c.company', ':searchTerm'),
                        $qb->expr()->like('c.vatId', ':searchTerm'),
                        $qb->expr()->like('c.number', ':searchTerm'),
                        $qb->expr()->like('c.contact', ':searchTerm'),
                        $qb->expr()->like('c.phone', ':searchTerm'),
                        $qb->expr()->like('c.email', ':searchTerm'),
                        $qb->expr()->like('c.address', ':searchTerm')
                    )
                );
                $qb->setParameter('searchTerm', '%' . $searchTerm->getSearchTerm() . '%');
            }

            if ($searchAnd->count() > 0) {
                $qb->andWhere($searchAnd);
            }
        }

        return $qb;
    }

    public function getPagerfantaForQuery(CustomerQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    public function countCustomersForQuery(CustomerQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($qb->expr()->countDistinct('c.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    protected function getPaginatorForQuery(CustomerQuery $query): PaginatorInterface
    {
        $counter = $this->countCustomersForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new LoaderPaginator(new CustomerLoader($qb->getEntityManager()), $qb, $counter);
    }

    /**
     * @param CustomerQuery $query
     * @return Customer[]
     */
    public function getCustomersForQuery(CustomerQuery $query): iterable
    {
        // this is using the paginator internally, as it will load all joined entities into the working unit
        // do not "optimize" to use the query directly, as it would results in hundreds of additional lazy queries
        $paginator = $this->getPaginatorForQuery($query);

        return $paginator->getAll();
    }

    /**
     * @param Customer $delete
     * @param Customer|null $replace
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteCustomer(Customer $delete, ?Customer $replace = null)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if (null !== $replace) {
                $qb = $em->createQueryBuilder();
                $qb
                    ->update(Project::class, 'p')
                    ->set('p.customer', ':replace')
                    ->where('p.customer = :delete')
                    ->setParameter('delete', $delete)
                    ->setParameter('replace', $replace)
                    ->getQuery()
                    ->execute();
            }

            $em->remove($delete);
            $em->flush();
            $em->commit();
        } catch (ORMException $ex) {
            $em->rollback();
            throw $ex;
        }
    }

    public function getComments(Customer $customer): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('comments')
            ->from(CustomerComment::class, 'comments')
            ->andWhere($qb->expr()->eq('comments.customer', ':customer'))
            ->addOrderBy('comments.pinned', 'DESC')
            ->addOrderBy('comments.createdAt', 'DESC')
            ->setParameter('customer', $customer)
        ;

        return $qb->getQuery()->getResult();
    }

    public function saveComment(CustomerComment $comment)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($comment);
        $entityManager->flush();
    }

    public function deleteComment(CustomerComment $comment)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($comment);
        $entityManager->flush();
    }
}

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
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class CustomerRepository extends EntityRepository
{
    /**
     * @param mixed $id
     * @param null $lockMode
     * @param null $lockVersion
     * @return Customer|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        /** @var Customer|null $customer */
        $customer = parent::find($id, $lockMode, $lockVersion);
        if (null === $customer) {
            return null;
        }

        $loader = new CustomerLoader($this->getEntityManager());
        $loader->loadResults([$customer]);

        return $customer;
    }

    /**
     * @param Customer $customer
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveCustomer(Customer $customer)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($customer);
        $entityManager->flush();
    }

    /**
     * @param null|bool $visible
     * @return int
     */
    public function countCustomer($visible = null)
    {
        if (null !== $visible) {
            return $this->count(['visible' => (bool) $visible]);
        }

        return $this->count([]);
    }

    /**
     * Retrieves statistics for one customer.
     *
     * @param Customer $customer
     * @return CustomerStatistic
     */
    public function getCustomerStatistics(Customer $customer)
    {
        $stats = new CustomerStatistic();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->addSelect('COUNT(t.id) as recordAmount')
            ->addSelect('SUM(t.duration) as recordDuration')
            ->addSelect('SUM(t.rate) as recordRate')
            ->addSelect('SUM(t.internalRate) as recordInternalRate')
            ->from(Timesheet::class, 't')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
            ->andWhere('p.customer = :customer')
        ;
        $timesheetResult = $qb->getQuery()->execute(['customer' => $customer], Query::HYDRATE_ARRAY);

        if (isset($timesheetResult[0])) {
            $stats->setRecordAmount($timesheetResult[0]['recordAmount']);
            $stats->setRecordDuration($timesheetResult[0]['recordDuration']);
            $stats->setRecordRate($timesheetResult[0]['recordRate']);
            $stats->setRecordInternalRate($timesheetResult[0]['recordInternalRate']);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->addSelect('COUNT(a.id) as activityAmount')
            ->from(Activity::class, 'a')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 'a.project = p.id')
            ->andWhere('a.project = p.id')
            ->andWhere('p.customer = :customer')
        ;
        $activityResult = $qb->getQuery()->execute(['customer' => $customer], Query::HYDRATE_ARRAY);

        if (isset($activityResult[0])) {
            $stats->setActivityAmount($activityResult[0]['activityAmount']);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->addSelect('COUNT(p.id) as projectAmount')
            ->from(Project::class, 'p')
            ->andWhere('p.customer = :customer')
        ;
        $projectResult = $qb->getQuery()->execute(['customer' => $customer], Query::HYDRATE_ARRAY);

        if (isset($projectResult[0])) {
            $stats->setProjectAmount($projectResult[0]['projectAmount']);
        }

        return $stats;
    }

    private function addPermissionCriteria(QueryBuilder $qb, ?User $user = null, array $teams = [])
    {
        // make sure that all queries without a user see all customers
        if (null === $user && empty($teams)) {
            return;
        }

        // make sure that admins see all customers
        if (null !== $user && ($user->isSuperAdmin() || $user->isAdmin())) {
            return;
        }

        if (null !== $user) {
            $teams = array_merge($teams, $user->getTeams()->toArray());
        }

        $qb->leftJoin('c.teams', 'teams');

        if (empty($teams)) {
            $qb->andWhere($qb->expr()->isNull('teams'));

            return;
        }

        $or = $qb->expr()->orX(
            $qb->expr()->isNull('teams'),
            $qb->expr()->isMemberOf(':teams', 'c.teams')
        );
        $qb->andWhere($or);

        $qb->setParameter('teams', $teams);
    }

    /**
     * @deprecated since 1.1 - use getQueryBuilderForFormType() istead - will be removed with 2.0
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

        $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
        $qb->setParameter('visible', true, \PDO::PARAM_BOOL);

        if ($query->hasCustomers()) {
            $qb->orWhere($qb->expr()->in('c.id', ':customer'))
                ->setParameter('customer', $query->getCustomers());
        }

        if (null !== $query->getCustomerToIgnore()) {
            $qb->andWhere($qb->expr()->neq('c.id', ':ignored'));
            $qb->setParameter('ignored', $query->getCustomerToIgnore());
        }

        $this->addPermissionCriteria($qb, $query->getUser(), $query->getTeams());

        return $qb;
    }

    private function getQueryBuilderForQuery(CustomerQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('c')
            ->from(Customer::class, 'c')
        ;

        $orderBy = 'c.' . $query->getOrderBy();
        $qb->orderBy($orderBy, $query->getOrder());

        if (CustomerQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', true, \PDO::PARAM_BOOL);
        } elseif (CustomerQuery::SHOW_HIDDEN == $query->getVisibility()) {
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

        // this will make sure, that we do not accidentally create results with multiple rows
        //   => which would result in a wrong LIMIT / pagination results
        // the second group by is needed due to SQL standard (even though logically not really required for this query)
        $qb->addGroupBy('c.id')->addGroupBy($orderBy);

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

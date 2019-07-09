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
use App\Entity\Project;
use App\Entity\Timesheet;
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
            ->from(Timesheet::class, 't')
            ->join(Project::class, 'p', Query\Expr\Join::WITH, 't.project = p.id')
            ->andWhere('p.customer = :customer')
        ;
        $timesheetResult = $qb->getQuery()->execute(['customer' => $customer], Query::HYDRATE_ARRAY);

        if (isset($timesheetResult[0])) {
            $stats->setRecordAmount($timesheetResult[0]['recordAmount']);
            $stats->setRecordDuration($timesheetResult[0]['recordDuration']);
            $stats->setRecordRate($timesheetResult[0]['recordRate']);
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

        $customer = $query->getCustomer();
        if (null !== $customer) {
            $qb->orWhere('c.id = :customer')->setParameter('customer', $customer);
        }

        if (null !== $query->getCustomerToIgnore()) {
            $qb->andWhere($qb->expr()->neq('c.id', ':ignored'));
            $qb->setParameter('ignored', $query->getCustomerToIgnore());
        }

        return $qb;
    }

    private function getQueryBuilderForQuery(CustomerQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c', 'meta')
            ->from(Customer::class, 'c')
            ->leftJoin('c.meta', 'meta')
            ->orderBy('c.' . $query->getOrderBy(), $query->getOrder());

        if (CustomerQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', true, \PDO::PARAM_BOOL);
        } elseif (CustomerQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', false, \PDO::PARAM_BOOL);
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

    protected function getPaginatorForQuery(CustomerQuery $query): PaginatorInterface
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('c.id'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

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
}

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
use App\Repository\Query\CustomerQuery;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class CustomerRepository extends AbstractRepository
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
     * @param int $id
     * @return null|Customer
     */
    public function getById($id)
    {
        return $this->find($id);
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
     * @param Customer|null $entity
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function builderForEntityType(Customer $entity = null)
    {
        $query = new CustomerQuery();
        $query->setHiddenEntity($entity);
        $query->setResultType(CustomerQuery::RESULT_TYPE_QUERYBUILDER);
        $query->setOrderBy('name');

        return $this->findByQuery($query);
    }

    /**
     * @param CustomerQuery $query
     * @return QueryBuilder|Pagerfanta|array
     */
    public function findByQuery(CustomerQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Customer::class, 'c')
            ->orderBy('c.' . $query->getOrderBy(), $query->getOrder());

        if (CustomerQuery::SHOW_VISIBLE == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', true, \PDO::PARAM_BOOL);

            /** @var Customer $entity */
            $entity = $query->getHiddenEntity();
            if (null !== $entity) {
                $qb->orWhere('c.id = :customer')->setParameter('customer', $entity);
            }
        } elseif (CustomerQuery::SHOW_HIDDEN == $query->getVisibility()) {
            $qb->andWhere($qb->expr()->eq('c.visible', ':visible'));
            $qb->setParameter('visible', false, \PDO::PARAM_BOOL);
        }

        if (!empty($query->getIgnoredEntities())) {
            $qb->andWhere('c.id NOT IN(:ignored)');
            $qb->setParameter('ignored', $query->getIgnoredEntities());
        }

        return $this->getBaseQueryResult($qb, $query);
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

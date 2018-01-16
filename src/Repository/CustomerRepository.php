<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\Timesheet;
use Doctrine\ORM\Query;
use App\Entity\Customer;
use App\Model\CustomerStatistic;
use App\Repository\Query\CustomerQuery;

/**
 * Class CustomerRepository
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerRepository extends AbstractRepository
{

    /**
     * @param $id
     * @return null|Customer
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * Return statistic data for all customer.
     *
     * @return CustomerStatistic
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getGlobalStatistics()
    {
        $countAll = $this->getEntityManager()
            ->createQuery('SELECT COUNT(c.id) FROM '.Customer::class.' c')
            ->getSingleScalarResult();

        $stats = new CustomerStatistic();
        $stats->setCount($countAll);
        return $stats;
    }

    /**
     * Retrieves statistics for one customer.
     *
     * @param Customer $customer
     * @return CustomerStatistic
     */
    public function getCustomerStatistics(Customer $customer)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(t.id) as recordAmount')
            ->addSelect('SUM(t.duration) as recordDuration')
            ->addSelect('COUNT(DISTINCT(a.id)) as activityAmount')
            ->addSelect('COUNT(DISTINCT(p.id)) as projectAmount')
            ->from(Timesheet::class, 't')
            ->join(Activity::class, 'a')
            ->join(Project::class, 'p')
            ->join(Customer::class, 'c')
            ->andWhere('t.activity = a.id')
            ->andWhere('a.project = p.id')
            ->andWhere('p.customer = c.id')
            ->andWhere('c.id = :customer')
        ;

        $result = $qb->getQuery()->execute(['customer' => $customer], Query::HYDRATE_ARRAY);

        $stats = new CustomerStatistic();

        if (isset($result[0])) {
            $dbStats = $result[0];

            $stats->setCount(1);
            $stats->setRecordAmount($dbStats['recordAmount']);
            $stats->setRecordDuration($dbStats['recordDuration']);
            $stats->setActivityAmount($dbStats['activityAmount']);
            $stats->setProjectAmount($dbStats['projectAmount']);
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
        return $this->findByQuery($query);
    }

    /**
     * @param CustomerQuery $query
     * @return \Doctrine\ORM\QueryBuilder|\Pagerfanta\Pagerfanta
     */
    public function findByQuery(CustomerQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c')
            ->from(Customer::class, 'c')
            ->orderBy('c.' . $query->getOrderBy(), $query->getOrder());

        if ($query->getVisibility() == CustomerQuery::SHOW_VISIBLE) {
            $qb->andWhere('c.visible = 1');

            /** @var Customer $entity */
            $entity = $query->getHiddenEntity();
            if ($entity!== null) {
                $qb->orWhere('c.id = :customer')->setParameter('customer', $entity);
            }
        } elseif ($query->getVisibility() == CustomerQuery::SHOW_HIDDEN) {
            $qb->andWhere('c.visible = 0');
        }

        return $this->getBaseQueryResult($qb, $query);
    }
}

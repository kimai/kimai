<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Repository;

use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Model\CustomerStatistic;

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
            ->createQuery('SELECT COUNT(a.id) FROM TimesheetBundle:Customer c')
            ->getSingleScalarResult();

        $stats = new CustomerStatistic();
        $stats->setTotalAmount($countAll);
        return $stats;
    }
}

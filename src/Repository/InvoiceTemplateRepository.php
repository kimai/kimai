<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\InvoiceTemplate;
use App\Repository\Query\BaseQuery;
use Doctrine\ORM\Query;

/**
 * Class InvoiceTemplateRepository
 */
class InvoiceTemplateRepository extends AbstractRepository
{

    /**
     * @return bool
     */
    public function hasTemplate()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(t.id) as totalRecords')
            ->from(InvoiceTemplate::class, 't')
        ;

        $result = $qb->getQuery()->execute([], Query::HYDRATE_ARRAY);

        if (!isset($result[0])) {
            return false;
        }

        return $result[0]['totalRecords'] > 0;
    }

    /**
     * @param BaseQuery $query
     * @return \Doctrine\ORM\QueryBuilder|\Pagerfanta\Pagerfanta
     */
    public function findByQuery(BaseQuery $query)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(InvoiceTemplate::class, 't')
            ->orderBy('t.id');

        return $this->getBaseQueryResult($qb, $query);
    }
}

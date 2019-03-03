<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

class TagRepository extends AbstractRepository
{
    /**
     * Find all tag names in an alphabetical order
     *
     * @param string $filter
     * @return array
     */
    public function findAllTagNames($filter = null)
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->select('t.name')
            ->addOrderBy('t.name', 'ASC');

        if (null !== $filter) {
            $qb
                ->andWhere('t.name LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }

        return array_column($qb->getQuery()->getScalarResult(), 'name');
    }
}

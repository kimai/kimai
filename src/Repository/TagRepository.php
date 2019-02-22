<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

/**
 * Class TagRepository
 *
 * @package App\Repository
 */
class TagRepository extends AbstractRepository
{

    /**
     * Find ids of the given list of tagNames
     * @param $tagNameList
     * @return array
     */
    public function findIdsByTagNameList($tagNameList)
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->select('t.id');
        $list = array_filter(array_unique(array_map('trim', explode(',', $tagNameList))));
        $cnt = 0;
        foreach ($list as $listElem) {
            $qb
                ->orWhere('t.tagName like :elem' . $cnt)
                ->setParameter('elem' . $cnt, '%' . $listElem . '%');
            $cnt++;
        }

        return array_column($qb->getQuery()->getScalarResult(), 'id');
    }

    /**
     * Find all tag names in an alphabetical order
     */
    public function findAllTagNamesAlphabetical($filter = NULL)
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->select('t.tagName')
            ->addOrderBy('t.tagName', 'ASC');
        if ($qb != NULL) {
            $qb
                ->andWhere('t.tagName LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }

        return array_column($qb->getQuery()->getScalarResult(), 'tagName');
    }

}
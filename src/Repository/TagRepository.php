<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2018-12-21
 * Time: 07:47
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
        dump($tagNameList);
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

}
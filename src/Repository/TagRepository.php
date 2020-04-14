<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Tag;
use App\Repository\Query\TagFormTypeQuery;
use App\Repository\Query\TagQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class TagRepository extends EntityRepository
{
    /**
     * @param Tag $tag
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTag(Tag $tag)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($tag);
        $entityManager->flush();
    }

    /**
     * @param Tag $tag
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteTag(Tag $tag)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($tag);
        $entityManager->flush();
    }

    public function findTagByName(string $tagName): ?Tag
    {
        return $this->findOneBy(['name' => $tagName]);
    }

    /**
     * Find ids of the given tagNames separated by comma
     * @param string $tagNames
     * @return array
     */
    public function findIdsByTagNameList($tagNames)
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->select('t.id');
        $list = array_filter(array_unique(array_map('trim', explode(',', $tagNames))));
        $cnt = 0;
        foreach ($list as $listElem) {
            $qb
                ->orWhere('t.name like :elem' . $cnt)
                ->setParameter('elem' . $cnt, '%' . $listElem . '%');
            $cnt++;
        }

        return array_column($qb->getQuery()->getScalarResult(), 'id');
    }

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

    /**
     * Returns an array of arrays with each inner array having the structure:
     * - id
     * - name
     * - amount
     *
     * @param TagQuery $query
     * @return Pagerfanta
     */
    public function getTagCount(TagQuery $query)
    {
        $qb = $this->createQueryBuilder('tag');

        $qb
            ->select('tag.id, tag.name, tag.color, count(timesheets.id) as amount')
            ->leftJoin('tag.timesheets', 'timesheets')
            ->addGroupBy('tag.id')
            ->addGroupBy('tag.name')
            ->addGroupBy('tag.color')
        ;

        $orderBy = $query->getOrderBy();
        switch ($orderBy) {
            case 'amount':
                $orderBy = 'amount';
                break;
            default:
                $orderBy = 'tag.' . $orderBy;
                break;
        }

        $qb->addOrderBy($orderBy, $query->getOrder());

        if ($query->hasSearchTerm()) {
            $searchTerm = $query->getSearchTerm();
            $searchAnd = $qb->expr()->andX();

            if ($searchTerm->hasSearchTerm()) {
                $searchAnd->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('tag.name', ':searchTerm')
                    )
                );
                $qb->setParameter('searchTerm', '%' . $searchTerm->getSearchTerm() . '%');
            }

            if ($searchAnd->count() > 0) {
                $qb->andWhere($searchAnd);
            }
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($qb->getQuery(), false));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    public function getQueryBuilderForFormType(TagFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('tag');

        $qb->orderBy('tag.name', 'ASC');

        return $qb;
    }

    /**
     * @param Tag[] $tags
     * @throws \Exception
     */
    public function multiDelete(iterable $tags): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($tags as $tag) {
                $em->remove($tag);
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }
}

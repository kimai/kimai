<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Tag;
use App\Repository\Paginator\QueryBuilderPaginator;
use App\Repository\Query\TagFormTypeQuery;
use App\Repository\Query\TagQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends \Doctrine\ORM\EntityRepository<Tag>
 */
class TagRepository extends EntityRepository
{
    /**
     * See KimaiFormSelect.js (maxOptions) as well.
     */
    public const MAX_AMOUNT_SELECT = 500;

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

    /**
     * @param array $tagNames
     * @return array<Tag>
     */
    public function findTagsByName(array $tagNames): array
    {
        return $this->findBy(['name' => $tagNames]);
    }

    public function findTagByName(string $tagName): ?Tag
    {
        return $this->findOneBy(['name' => $tagName]);
    }

    /**
     * Find all tag names in an alphabetical order
     *
     * @param string $filter
     * @return array
     */
    public function findAllTagNames($filter = null): array
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
     * @return Pagination
     */
    public function getTagCount(TagQuery $query): Pagination
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->count('tag.name'))
        ;
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $qb = $this->getQueryBuilderForQuery($query);

        $paginator = new QueryBuilderPaginator($qb, $counter);

        $pager = new Pagination($paginator);
        $pager->setMaxPerPage($query->getPageSize());
        $pager->setCurrentPage($query->getPage());

        return $pager;
    }

    private function getQueryBuilderForQuery(TagQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('tag');

        $qb->select('tag.id, tag.name, tag.color, SIZE(tag.timesheets) as amount');

        $orderBy = $query->getOrderBy();
        $orderBy = match ($orderBy) {
            'amount' => 'amount',
            default => 'tag.' . $orderBy,
        };

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

        return $qb;
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

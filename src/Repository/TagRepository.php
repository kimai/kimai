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
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityRepository;
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

    public function saveTag(Tag $tag): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($tag);
        $entityManager->flush();
    }

    public function deleteTag(Tag $tag): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($tag);
        $entityManager->flush();
    }

    /**
     * @param array<string> $tagNames
     * @return array<Tag>
     */
    public function findTagsByName(array $tagNames, ?bool $visible = null): array
    {
        if ($visible === null) {
            return $this->findBy(['name' => $tagNames]);
        }

        return $this->findBy(['name' => $tagNames, 'visible' => $visible]);
    }

    public function findTagByName(string $tagName, ?bool $visible = null): ?Tag
    {
        if ($visible === null) {
            return $this->findOneBy(['name' => $tagName]);
        }

        return $this->findOneBy(['name' => $tagName, 'visible' => $visible]);
    }

    /**
     * Find all visible tag names in alphabetical order.
     *
     * @return array<string>
     */
    public function findAllTagNames(?string $filter = null): array
    {
        $qb = $this->createQueryBuilder('t');

        $qb
            ->select('t.name')
            ->addOrderBy('t.name', 'ASC');

        $qb->andWhere($qb->expr()->eq('t.visible', ':visible'));
        $qb->setParameter('visible', true, ParameterType::BOOLEAN);

        if (null !== $filter) {
            $qb->andWhere('t.name LIKE :filter');
            $qb->setParameter('filter', '%' . $filter . '%');
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

        $qb->select('tag.id, tag.name, tag.color, tag.visible, SIZE(tag.timesheets) as amount');

        $orderBy = $query->getOrderBy();
        $orderBy = match ($orderBy) {
            'amount' => 'amount',
            default => 'tag.' . $orderBy,
        };

        if ($query->isShowVisible()) {
            $qb->andWhere($qb->expr()->eq('tag.visible', ':visible'));
            $qb->setParameter('visible', true, ParameterType::BOOLEAN);
        } elseif ($query->isShowHidden()) {
            $qb->andWhere($qb->expr()->eq('tag.visible', ':visible'));
            $qb->setParameter('visible', false, ParameterType::BOOLEAN);
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

        return $qb;
    }

    public function getQueryBuilderForFormType(TagFormTypeQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('tag');

        $qb->orderBy('tag.name', 'ASC');
        $qb->andWhere($qb->expr()->eq('tag.visible', ':visible'));
        $qb->setParameter('visible', true, ParameterType::BOOLEAN);

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

    /**
     * @param Tag[] $tags
     * @throws \Exception
     */
    public function multiUpdate(iterable $tags): void
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($tags as $tag) {
                $em->persist($tag);
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $ex) {
            $em->rollback();
            throw $ex;
        }
    }
}

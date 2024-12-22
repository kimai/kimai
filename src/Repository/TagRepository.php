<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Repository\Paginator\QueryPaginator;
use App\Repository\Query\TagFormTypeQuery;
use App\Repository\Query\TagQuery;
use App\Utils\Pagination;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Tag>
 */
class TagRepository extends EntityRepository
{
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

    private function findAllTagsQuery(?string $filter = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->addOrderBy('t.name', 'ASC');

        $qb->andWhere($qb->expr()->eq('t.visible', ':visible'));
        $qb->setParameter('visible', true, ParameterType::BOOLEAN);

        if (null !== $filter) {
            $qb->andWhere('t.name LIKE :filter');
            $qb->setParameter('filter', '%' . $filter . '%');
        }

        return $qb;
    }

    /**
     * Find all visible tag names in alphabetical order.
     *
     * @return array<Tag>
     */
    public function findAllTags(?string $filter = null): array
    {
        return $this->findAllTagsQuery($filter)->getQuery()->getResult();
    }

    /**
     * Find all visible tag names in alphabetical order.
     *
     * @return array<string>
     */
    public function findAllTagNames(?string $filter = null): array
    {
        return array_column($this->findAllTagsQuery($filter)->select('t.name')->getQuery()->getScalarResult(), 'name');
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
        $qb1 = clone $qb;

        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->count('tag'))
        ;
        /** @var int<0, max> $counter */
        $counter = (int) $qb->getQuery()->getSingleScalarResult();

        $paginator = new QueryPaginator($qb1->getQuery(), $counter);

        $pager = new Pagination($paginator);
        $pager->setMaxPerPage($query->getPageSize());
        $pager->setCurrentPage($query->getPage());

        return $pager;
    }

    private function getQueryBuilderForQuery(TagQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('tag');

        $qb1 = $this->getEntityManager()->createQueryBuilder();
        $qb1->from(Timesheet::class, 't')->select('COUNT(tags)')->innerJoin('t.tags', 'tags')->where('tags.id = tag.id');

        $dql = $qb1->getDQL(); // see https://github.com/phpstan/phpstan-doctrine/issues/606
        $qb->select('tag.id, tag.name, tag.color, tag.visible');
        $qb->addSelect('(' . $dql . ') as amount');

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

        $searchTerm = $query->getSearchTerm();
        if ($searchTerm !== null) {
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

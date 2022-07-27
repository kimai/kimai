<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\InvoiceTemplate;
use App\Repository\Paginator\PaginatorInterface;
use App\Repository\Paginator\QueryBuilderPaginator;
use App\Repository\Query\BaseQuery;
use App\Utils\Pagination;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends \Doctrine\ORM\EntityRepository<InvoiceTemplate>
 */
class InvoiceTemplateRepository extends EntityRepository
{
    public function hasTemplate(): bool
    {
        return $this->count([]) > 0;
    }

    public function getQueryBuilderForFormType(): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(InvoiceTemplate::class, 't')
            ->orderBy('t.name');

        return $qb;
    }

    private function getQueryBuilderForQuery(BaseQuery $query): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t')
            ->from(InvoiceTemplate::class, 't')
            ->orderBy('t.name');

        return $qb;
    }

    protected function getPaginatorForQuery(BaseQuery $query): PaginatorInterface
    {
        $counter = $this->countTemplatesForQuery($query);
        $qb = $this->getQueryBuilderForQuery($query);

        return new QueryBuilderPaginator($qb, $counter);
    }

    public function getPagerfantaForQuery(BaseQuery $query): Pagination
    {
        $paginator = new Pagination($this->getPaginatorForQuery($query));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    public function countTemplatesForQuery(BaseQuery $query): int
    {
        $qb = $this->getQueryBuilderForQuery($query);
        $qb
            ->resetDQLPart('select')
            ->resetDQLPart('orderBy')
            ->select($qb->expr()->countDistinct('t.id'))
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function saveTemplate(InvoiceTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function removeTemplate(InvoiceTemplate $template): void
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }
}

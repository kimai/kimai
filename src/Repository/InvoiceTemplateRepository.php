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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class InvoiceTemplateRepository extends EntityRepository
{
    public function findTemplate(string $idOrName): ?InvoiceTemplate
    {
        $tpl = $this->find($idOrName);
        if (null !== $tpl) {
            return $tpl;
        }

        return $this->findOneBy(['name' => $idOrName]);
    }

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

    public function getPagerfantaForQuery(BaseQuery $query): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($this->getQueryBuilderForQuery($query), false));
        $paginator->setMaxPerPage($query->getPageSize());
        $paginator->setCurrentPage($query->getPage());

        return $paginator;
    }

    /**
     * @param InvoiceTemplate $template
     * @return InvoiceTemplate
     * @throws RepositoryException
     */
    public function saveTemplate(InvoiceTemplate $template)
    {
        try {
            $this->getEntityManager()->persist($template);
            $this->getEntityManager()->flush();
        } catch (\Exception $ex) {
            throw new RepositoryException('Could not save InvoiceTemplate');
        }

        return $template;
    }

    /**
     * @param InvoiceTemplate $template
     * @throws RepositoryException
     */
    public function removeTemplate(InvoiceTemplate $template)
    {
        try {
            $this->getEntityManager()->remove($template);
            $this->getEntityManager()->flush();
        } catch (\Exception $ex) {
            throw new RepositoryException('Could not remove InvoiceTemplate');
        }
    }
}

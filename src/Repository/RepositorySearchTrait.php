<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Repository\Query\BaseQuery;
use Doctrine\ORM\QueryBuilder;

trait RepositorySearchTrait
{
    private function getMetaFieldClass(): ?string
    {
        return null;
    }

    private function getMetaFieldName(): ?string
    {
        return null;
    }

    /**
     * @return array<string>
     */
    private function getSearchableFields(): array
    {
        return [];
    }

    private function supportsMetaFields(): bool
    {
        /* @phpstan-ignore-next-line  */
        return $this->getMetaFieldClass() !== null && $this->getMetaFieldName() !== null;
    }

    private function addSearchTerm(QueryBuilder $qb, BaseQuery $query): void
    {
        if (!$query->hasSearchTerm()) {
            return;
        }

        $searchTerm = $query->getSearchTerm();

        if (!$this->supportsMetaFields() && !$searchTerm->hasSearchTerm()) {
            return;
        }

        $aliases = $qb->getRootAliases();
        if (!isset($aliases[0])) {
            throw new RepositoryException('No alias was set before invoking addSearchTerm().');
        }
        $rootAlias = $aliases[0];

        $searchAnd = $qb->expr()->andX();

        if ($this->supportsMetaFields()) {
            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $and = $qb->expr()->andX();

                if ($metaValue === '*') {
                    $qb->leftJoin($rootAlias . '.meta', 'meta');
                    $and->add($qb->expr()->eq('meta.name', ':metaName'));
                    $qb->setParameter('metaName', $metaName);
                    $and->add($qb->expr()->isNotNull('meta.value'));
                } elseif ($metaValue === '~') {
                    $and->add(
                        sprintf('NOT EXISTS(SELECT metaNotExists FROM %s metaNotExists WHERE metaNotExists.%s = %s.id)', $this->getMetaFieldClass(), $this->getMetaFieldName(), $rootAlias)
                    );
                } elseif ($metaValue === '' || $metaValue === null) {
                    $qb->leftJoin($rootAlias . '.meta', 'meta');
                    $and->add(
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq('meta.name', ':metaName'),
                                $qb->expr()->isNull('meta.value')
                            ),
                            sprintf('NOT EXISTS(SELECT metaNotExists FROM %s metaNotExists WHERE metaNotExists.%s = %s.id)', $this->getMetaFieldClass(), $this->getMetaFieldName(), $rootAlias)
                        )
                    );
                    $qb->setParameter('metaName', $metaName);
                } else {
                    $qb->leftJoin($rootAlias . '.meta', 'meta');
                    $and->add($qb->expr()->eq('meta.name', ':metaName'));
                    $and->add($qb->expr()->like('meta.value', ':metaValue'));
                    $qb->setParameter('metaName', $metaName);
                    $qb->setParameter('metaValue', '%' . $metaValue . '%');
                }

                $searchAnd->add($and);
            }
        }

        $fields = $this->getSearchableFields();

        if ($searchTerm->hasSearchTerm() && \count($fields) > 0) {
            $or = $qb->expr()->orX();
            foreach ($fields as $field) {
                if (stripos($field, '.') === false) {
                    $field = $rootAlias . '.' . $field;
                }
                $or->add(
                    $qb->expr()->like($field, ':searchTerm'),
                );
            }
            $searchAnd->add($or);
            $qb->setParameter('searchTerm', '%' . $searchTerm->getSearchTerm() . '%');
        }

        if ($searchAnd->count() > 0) {
            $qb->andWhere($searchAnd);
        }
    }
}

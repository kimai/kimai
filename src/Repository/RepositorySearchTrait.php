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
            $i = 0;
            $a = 0;
            $c = 0;
            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $and = $qb->expr()->andX();
                /** @var literal-string $alias */
                $alias = 'meta' . $a++;
                $paramName = 'metaName' . $i++;
                $paramValue = 'metaValue' . $c++;

                if ($metaValue === '*') {
                    $qb->leftJoin($rootAlias . '.meta', $alias);
                    $and->add($qb->expr()->eq($alias . '.name', ':' . $paramName));
                    $qb->setParameter($paramName, $metaName);
                    $and->add($qb->expr()->isNotNull($alias . '.value'));
                } elseif ($metaValue === '~') {
                    $and->add(
                        sprintf('NOT EXISTS(SELECT metaNotExists FROM %s metaNotExists WHERE metaNotExists.%s = %s.id)', $this->getMetaFieldClass(), $this->getMetaFieldName(), $rootAlias)
                    );
                } elseif ($metaValue === '' || $metaValue === null) {
                    $qb->leftJoin($rootAlias . '.meta', $alias);
                    $and->add(
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq($alias . '.name', ':' . $paramName),
                                $qb->expr()->isNull($alias . '.value')
                            ),
                            sprintf('NOT EXISTS(SELECT metaNotExists FROM %s metaNotExists WHERE metaNotExists.%s = %s.id)', $this->getMetaFieldClass(), $this->getMetaFieldName(), $rootAlias)
                        )
                    );
                    $qb->setParameter($paramName, $metaName);
                } else {
                    $qb->leftJoin($rootAlias . '.meta', $alias);
                    $and->add($qb->expr()->eq($alias . '.name', ':' . $paramName));
                    $and->add($qb->expr()->like($alias . '.value', ':' . $paramValue));
                    $qb->setParameter($paramName, $metaName);
                    $qb->setParameter($paramValue, '%' . $metaValue . '%');
                }

                $searchAnd->add($and);
            }
        }

        $fields = $this->getSearchableFields();

        if ($searchTerm->hasSearchTerm() && \count($fields) > 0) {
            $or = $qb->expr()->orX();
            $i = 0;
            foreach ($fields as $field) {
                $param = 'searchTerm' . $i++;
                if (stripos($field, '.') === false) {
                    $field = $rootAlias . '.' . $field;
                }
                $or->add(
                    $qb->expr()->like($field, ':' . $param),
                );
                $qb->setParameter($param, '%' . $searchTerm->getSearchTerm() . '%');
            }
            $searchAnd->add($or);
        }

        if ($searchAnd->count() > 0) {
            $qb->andWhere($searchAnd);
        }
    }
}

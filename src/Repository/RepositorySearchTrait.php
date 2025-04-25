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
    /**
     * The name of the meta-field class, e.g. ProjectMeta::class.
     * Null if entity does not support meta-fields.
     */
    private function getMetaFieldClass(): ?string
    {
        return null;
    }

    /**
     * This is the attribute/field name of the parent class within the meta-field class from getMetaFieldClass()
     * Null if entity does not support meta-fields.
     */
    private function getMetaFieldName(): ?string
    {
        return null;
    }

    /**
     * This is the attribute/field name of meta-field class within the entity
     * Null if entity does not support meta-fields.
     */
    private function getEntityFieldName(): ?string
    {
        return 'meta';
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
        return $this->getMetaFieldClass() !== null && $this->getMetaFieldName() !== null && $this->getEntityFieldName() !== null;
    }

    private function addSearchTerm(QueryBuilder $qb, BaseQuery $query): void
    {
        $searchTerm = $query->getSearchTerm();

        if ($searchTerm === null) {
            return;
        }

        if (!$this->supportsMetaFields() && !$searchTerm->hasSearchTerm()) {
            return;
        }

        $aliases = $qb->getRootAliases();
        if (!isset($aliases[0])) {
            throw new RepositoryException('No alias was set before invoking addSearchTerm().');
        }
        $rootAlias = $aliases[0];
        $metaFieldRef = $rootAlias . '.' . $this->getEntityFieldName();

        $searchAnd = $qb->expr()->andX();

        if ($this->supportsMetaFields()) {
            $i = 0;
            $a = 0;
            $c = 0;
            foreach ($searchTerm->getSearchFields() as $metaName => $metaValue) {
                $and = $qb->expr()->andX();
                /** @var non-falsy-string&lowercase-string $alias */
                $alias = 'meta' . $a++;
                $paramName = 'metaName' . $i++;
                $paramValue = 'metaValue' . $c++;
                $subqueryName = 'metaNotExists' . $metaName;

                if ($metaValue === '*') {
                    $qb->leftJoin($metaFieldRef, $alias);
                    $and->add($qb->expr()->eq($alias . '.name', ':' . $paramName));
                    $qb->setParameter($paramName, $metaName);
                    $and->add($qb->expr()->isNotNull($alias . '.value'));
                } elseif ($metaValue === '~') {
                    $and->add(
                        \sprintf('NOT EXISTS(SELECT %s FROM %s %s WHERE %s.%s = %s.id)', $subqueryName, $this->getMetaFieldClass(), $subqueryName, $subqueryName, $this->getMetaFieldName(), $rootAlias)
                    );
                } elseif ($metaValue === '' || $metaValue === null) {
                    $qb->leftJoin($metaFieldRef, $alias);
                    $and->add(
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq($alias . '.name', ':' . $paramName),
                                $qb->expr()->isNull($alias . '.value')
                            ),
                            \sprintf('NOT EXISTS(SELECT %s FROM %s %s WHERE %s.%s = %s.id)', $subqueryName, $this->getMetaFieldClass(), $subqueryName, $subqueryName, $this->getMetaFieldName(), $rootAlias)
                        )
                    );
                    $qb->setParameter($paramName, $metaName);
                } else {
                    $qb->leftJoin($metaFieldRef, $alias);
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
                if (stripos($field, '.') === false) {
                    $field = $rootAlias . '.' . $field;
                }
                foreach ($searchTerm->getParts() as $part) {
                    // currently only meta fields have a name, so we do not use them here
                    if ($part->getField() !== null) {
                        continue;
                    }
                    $param = 'searchTerm' . $i++;
                    if ($part->isExcluded()) {
                        $searchAnd->add(
                            $qb->expr()->orX()->addMultiple([
                                $qb->expr()->isNull($field),
                                $qb->expr()->notLike($field, ':' . $param),
                            ])
                        );
                    } else {
                        $or->add(
                            $qb->expr()->like($field, ':' . $param),
                        );
                    }
                    $qb->setParameter($param, '%' . $part->getTerm() . '%');
                }
            }
            $searchAnd->add($or);
        }

        if ($searchAnd->count() > 0) {
            $qb->andWhere($searchAnd);
        }
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Search;

use App\Repository\Query\BaseQuery;
use App\Repository\RepositoryException;
use Doctrine\ORM\QueryBuilder;

final class SearchHelper
{
    public function __construct(private readonly SearchConfigurationInterface $configuration)
    {
    }

    private function supportsMetaFields(): bool
    {
        return $this->configuration->getMetaFieldClass() !== null && $this->configuration->getMetaFieldName() !== null && $this->configuration->getEntityFieldName() !== null;
    }

    public function addSearchTerm(QueryBuilder $qb, BaseQuery $query): void
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
        $searchAnd = $qb->expr()->andX();
        $metaFieldClass = $this->configuration->getMetaFieldClass();
        $metaFieldName = $this->configuration->getMetaFieldName();

        if ($metaFieldClass !== null && $metaFieldName !== null && $this->supportsMetaFields()) {
            $metaFieldRef = $rootAlias . '.' . $this->configuration->getEntityFieldName();
            $i = 0;
            $c = 0;
            $j = 0;
            foreach ($searchTerm->getParts() as $part) {
                // we do NOT search for unspecific/global terms as of now, because it is not clear if the user wants that
                if (($metaName = $part->getField()) === null) {
                    continue;
                }
                $alias = 'meta' . $j++;
                $qb->leftJoin($metaFieldRef, $alias);
                $metaValue = $part->getTerm();
                $paramName = 'metaName' . $i++;
                $paramValue = 'metaValue' . $c++;
                $subqueryName = 'metaNotExists' . $metaName;
                $field = $alias . '.value';

                $and = $qb->expr()->andX();

                if ($metaValue === '*') {
                    $and->add($qb->expr()->eq($alias . '.name', ':' . $paramName));
                    $and->add($qb->expr()->isNotNull($field));
                } elseif ($metaValue === '~') {
                    $and->add(
                        \sprintf('NOT EXISTS(SELECT %s FROM %s %s WHERE %s.%s = %s.id AND %s.name = :%s)', $subqueryName, $metaFieldClass, $subqueryName, $subqueryName, $metaFieldName, $rootAlias, $subqueryName, $paramName)
                    );
                } elseif ($metaValue === '') {
                    $and->add(
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq($alias . '.name', ':' . $paramName),
                                $qb->expr()->isNull($field)
                            ),
                            \sprintf('NOT EXISTS(SELECT %s FROM %s %s WHERE %s.%s = %s.id AND %s.name = :%s)', $subqueryName, $metaFieldClass, $subqueryName, $subqueryName, $metaFieldName, $rootAlias, $subqueryName, $paramName)
                        )
                    );
                } else {
                    $and->add($qb->expr()->eq($alias . '.name', ':' . $paramName));
                    if (!$part->isExcluded()) {
                        $and->add($qb->expr()->like($field, ':' . $paramValue));
                    } else {
                        $and->add(
                            $qb->expr()->orX()->addMultiple([
                                $qb->expr()->isNull($field),
                                $qb->expr()->notLike($field, ':' . $paramValue),
                            ])
                        );
                    }
                    $qb->setParameter($paramValue, '%' . $metaValue . '%');
                }

                $qb->setParameter($paramName, $metaName);

                $searchAnd->add($and);
            }
        }

        if ($searchTerm->hasSearchTerm()) {
            $i = 0;
            $fields = $this->configuration->getSearchableFields();
            $and = $qb->expr()->andX();
            foreach ($searchTerm->getParts() as $part) {
                // currently only meta fields have a name, so we do not use them here
                if ($part->getField() !== null) {
                    continue;
                }
                $or = $qb->expr()->orX();
                foreach ($fields as $field) {
                    if (stripos($field, '.') === false) {
                        $field = $rootAlias . '.' . $field;
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
                if ($or->count() > 0) {
                    $and->add($or);
                }
            }
            if ($and->count() > 0) {
                $searchAnd->add($and);
            }
        }

        if ($searchAnd->count() > 0) {
            $qb->andWhere($searchAnd);
        }
    }
}

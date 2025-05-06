<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Repository\Query\BaseQuery;
use App\Repository\Search\SearchConfiguration;
use App\Repository\Search\SearchHelper;
use Doctrine\ORM\QueryBuilder;

/**
 * @deprecated since 2.34.0 use SearchHelper instead
 */
trait RepositorySearchTrait // @phpstan-ignore trait.unused
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

    private function addSearchTerm(QueryBuilder $qb, BaseQuery $query): void
    {
        $configuration = new SearchConfiguration(
            $this->getSearchableFields(),
            $this->getMetaFieldClass(),
            $this->getMetaFieldName()
        );
        $configuration->setEntityFieldName($this->getEntityFieldName());

        $helper = new SearchHelper($configuration);
        $helper->addSearchTerm($qb, $query);
    }
}

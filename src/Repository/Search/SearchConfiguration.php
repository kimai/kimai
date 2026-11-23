<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Search;

class SearchConfiguration implements SearchConfigurationInterface
{
    private string $entityFieldName = 'meta';

    /**
     * @param array<string> $searchableFields
     */
    public function __construct(
        private readonly array $searchableFields = [],
        private readonly ?string $metaFieldClass = null,
        private readonly ?string $metaFieldName = null,
    )
    {
    }

    /**
     * The name of the meta-field class, e.g. ProjectMeta::class.
     * Null if entity does not support meta-fields.
     */
    public function getMetaFieldClass(): ?string
    {
        return $this->metaFieldClass;
    }

    /**
     * This is the attribute/field name of the parent class within the meta-field class from getMetaFieldClass()
     * Null if entity does not support meta-fields.
     */
    public function getMetaFieldName(): ?string
    {
        return $this->metaFieldName;
    }

    public function setEntityFieldName(string $entityFieldName): void
    {
        $this->entityFieldName = $entityFieldName;
    }

    /**
     * This is the attribute/field name of meta-field class within the entity
     * Null if entity does not support meta-fields.
     */
    public function getEntityFieldName(): ?string
    {
        return $this->entityFieldName;
    }

    /**
     * @return array<string>
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }
}

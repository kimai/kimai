<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Search;

interface SearchConfigurationInterface
{
    /**
     * The name of the meta-field class, e.g. ProjectMeta::class.
     * Null if entity does not support meta-fields.
     */
    public function getMetaFieldClass(): ?string;

    /**
     * This is the attribute/field name of the parent class within the meta-field class from getMetaFieldClass()
     * Null if entity does not support meta-fields.
     */
    public function getMetaFieldName(): ?string;

    /**
     * This is the attribute/field name of meta-field class within the entity
     * Null if entity does not support meta-fields.
     */
    public function getEntityFieldName(): ?string;

    /**
     * @return array<string>
     */
    public function getSearchableFields(): array;
}

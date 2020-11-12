<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\MetaTableTypeInterface;
use App\Repository\Query\BaseQuery;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractMetaDisplayEvent extends Event implements MetaDisplayEventInterface
{
    /**
     * @var BaseQuery
     */
    private $query;
    /**
     * @var string
     */
    private $location;
    /**
     * @var MetaTableTypeInterface[]
     */
    private $fields = [];

    public function __construct(BaseQuery $query, string $location)
    {
        $this->query = $query;
        $this->location = $location;
    }

    /**
     * To filter where your meta-field will be displayed, use the query settings.
     *
     * @return BaseQuery
     */
    public function getQuery(): BaseQuery
    {
        return $this->query;
    }

    /**
     * If you want to filter where your meta-field will be displayed, check the current location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Add a new meta field that should be included.
     *
     * @param MetaTableTypeInterface $meta
     */
    public function addField(MetaTableTypeInterface $meta)
    {
        $this->fields[] = $meta;
    }

    /**
     * Returns all meta-fields to be included.
     *
     * @return MetaTableTypeInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}

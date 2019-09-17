<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\MetaTableTypeInterface;
use App\Repository\Query\CustomerQuery;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dynamically find possible meta fields for a customer query.
 */
final class CustomerMetaQueryEvent extends Event
{
    /**
     * @var CustomerQuery
     */
    private $query;
    /**
     * @var MetaTableTypeInterface[]
     */
    private $fields = [];

    public function __construct(CustomerQuery $query)
    {
        $this->query = $query;
    }

    public function getQuery(): CustomerQuery
    {
        return $this->query;
    }

    public function addField(MetaTableTypeInterface $meta): CustomerMetaQueryEvent
    {
        $this->fields[] = $meta;

        return $this;
    }

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}

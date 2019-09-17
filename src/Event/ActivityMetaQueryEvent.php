<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\MetaTableTypeInterface;
use App\Repository\Query\ActivityQuery;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dynamically find possible meta fields for a activity query.
 */
final class ActivityMetaQueryEvent extends Event
{
    /**
     * @var ActivityQuery
     */
    private $query;
    /**
     * @var MetaTableTypeInterface[]
     */
    private $fields = [];

    public function __construct(ActivityQuery $query)
    {
        $this->query = $query;
    }

    public function getQuery(): ActivityQuery
    {
        return $this->query;
    }

    public function addField(MetaTableTypeInterface $meta): ActivityMetaQueryEvent
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

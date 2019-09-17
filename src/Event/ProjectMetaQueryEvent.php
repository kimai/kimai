<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\MetaTableTypeInterface;
use App\Repository\Query\ProjectQuery;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dynamically find possible meta fields for a project query.
 */
final class ProjectMetaQueryEvent extends Event
{
    /**
     * @var ProjectQuery
     */
    private $query;
    /**
     * @var MetaTableTypeInterface[]
     */
    private $fields = [];

    public function __construct(ProjectQuery $query)
    {
        $this->query = $query;
    }

    public function getQuery(): ProjectQuery
    {
        return $this->query;
    }

    public function addField(MetaTableTypeInterface $meta): ProjectMetaQueryEvent
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

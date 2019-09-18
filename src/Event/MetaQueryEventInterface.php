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

interface MetaQueryEventInterface
{
    /**
     * If you want to filter where your meta-field will be displayed, use the query settings.
     *
     * @return BaseQuery
     */
    public function getQuery(): BaseQuery;

    /**
     * If you want to filter where your meta-field will be displayed, check the current location.
     *
     * @return string
     */
    public function getLocation(): string;

    /**
     * @return MetaTableTypeInterface[]
     */
    public function getFields(): array;

    /**
     * Adds a field that should be displayed.
     *
     * @param MetaTableTypeInterface $meta
     */
    public function addField(MetaTableTypeInterface $meta);
}

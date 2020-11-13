<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\MetaTableTypeInterface;
use App\Invoice\InvoiceItemInterface;

interface ExportItemInterface extends InvoiceItemInterface
{
    /**
     * Whether this item was already exported.
     *
     * @return bool
     */
    public function isExported(): bool;

    /**
     * Returns the named meta field or null.
     *
     * @param string $name
     * @return MetaTableTypeInterface|null
     */
    public function getMetaField(string $name): ?MetaTableTypeInterface;

    /**
     * Returns all assigned tag names.
     *
     * @return string[]
     */
    public function getTagsAsArray(): array;
}

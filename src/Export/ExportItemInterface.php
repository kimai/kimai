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

/**
 * Will be merged with InvoiceItemInterface in 2.0
 */
interface ExportItemInterface extends InvoiceItemInterface
{
    /**
     * Whether this item was already exported.
     *
     * @return bool
     */
    public function isExported(): bool;

    /**
     * Whether this item should be included in invoices.
     *
     * @return bool
     */
    public function isBillable(): bool;

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

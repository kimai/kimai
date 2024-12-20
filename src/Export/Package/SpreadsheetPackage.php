<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package;

interface SpreadsheetPackage
{
    /**
     * Returns the temporary filename.
     */
    public function save(): string;

    /**
     * @param array<string> $columns
     */
    public function setHeader(array $columns): void;

    /**
     * @param array<int, mixed> $columns
     * @param array<string, mixed> $options
     */
    public function addRow(array $columns, array $options = []): void;
}

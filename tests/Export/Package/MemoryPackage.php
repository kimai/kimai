<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package;

use App\Export\Package\Column;
use App\Export\Package\SpreadsheetPackage;

class MemoryPackage implements SpreadsheetPackage
{
    private ?string $filename = null;
    /** @var array<Column> */
    private array $columns = [];
    private array $rows = [];
    private bool $saved = false;

    public function open(string $filename): void
    {
        $this->filename = $filename;
    }

    public function save(): void
    {
        $this->saved = true;
    }

    /**
     * @param array<Column> $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function addRow(array $columns, array $options = []): void
    {
        $this->rows[] = ['columns' => $columns, 'options' => $options];
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function isSaved(): bool
    {
        return $this->saved;
    }
}

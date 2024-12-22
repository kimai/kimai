<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package;

use App\Entity\ExportableItem;
use App\Export\Package\CellFormatter\CellFormatterInterface;

class Column
{
    private ?string $header = null;
    private \Closure|null $extractor = null;

    public function __construct(private readonly string $name, private readonly CellFormatterInterface $formatter)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withHeader(?string $header): Column
    {
        $this->header = $header;

        return $this;
    }

    public function withExtractor(\Closure $extractor): Column
    {
        $this->extractor = $extractor;

        return $this;
    }

    public function extract(ExportableItem $exportableItem): mixed
    {
        if ($this->extractor === null) {
            throw new \InvalidArgumentException('Missing extractor on column: ' . $this->name);
        }

        return ($this->extractor)($exportableItem);
    }

    public function getValue(ExportableItem $exportableItem): mixed
    {
        return $this->formatter->formatValue($this->extract($exportableItem));
    }

    public function getHeader(): string
    {
        return $this->header ?? $this->name;
    }
}

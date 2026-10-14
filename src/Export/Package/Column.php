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
use App\Export\Package\CellFormatter\CellWithFormatInterface;

class Column
{
    private ?string $header = null;
    /**
     * @var array<string, string>
     */
    private array $headerParams = [];
    private \Closure|null $extractor = null;
    private ColumnWidth $columnWidth = ColumnWidth::DEFAULT;

    public function __construct(private readonly string $name, private readonly CellFormatterInterface $formatter)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, string> $params translation parameters for the header
     */
    public function withHeader(?string $header, array $params = []): Column
    {
        $this->header = $header;
        $this->headerParams = $params;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaderParams(): array
    {
        return $this->headerParams;
    }

    public function withColumnWidth(ColumnWidth $columnWidth): Column
    {
        $this->columnWidth = $columnWidth;

        return $this;
    }

    public function getColumnWidth(): ColumnWidth
    {
        return $this->columnWidth;
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

    public function getFormat(): ?string
    {
        if ($this->formatter instanceof CellWithFormatInterface) {
            return $this->formatter->getFormat();
        }

        return null;
    }
}

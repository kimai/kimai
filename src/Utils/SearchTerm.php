<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class SearchTerm
{
    private string $originalTerm;
    private bool $hasSearchTerm = false;
    /**
     * @var SearchTermPart[]
     */
    private array $parts = [];

    public function __construct(string $searchTerm)
    {
        $this->originalTerm = $searchTerm;
        $terms = explode(' ', $searchTerm);
        $finalTerm = [];

        foreach ($terms as $term) {
            $part = new SearchTermPart($term);
            if ($part->getField() === null) {
                $this->hasSearchTerm = true;
            }
            $this->parts[] = $part;
        }
    }

    /**
     * @return SearchTermPart[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function hasSearchTerm(): bool
    {
        return $this->hasSearchTerm;
    }

    public function getOriginalSearch(): string
    {
        return $this->originalTerm;
    }

    public function __toString(): string
    {
        return $this->originalTerm;
    }
}

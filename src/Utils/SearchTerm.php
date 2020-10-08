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
    /**
     * @var string
     */
    private $originalTerm;
    /**
     * @var string
     */
    private $term;
    /**
     * @var string[]
     */
    private $fields = [];

    public function __construct(string $searchTerm)
    {
        $this->originalTerm = $searchTerm;
        $this->parse($searchTerm);
    }

    private function parse(string $searchTerm)
    {
        $terms = explode(' ', $searchTerm);
        $fields = [];
        $finalTerm = [];

        foreach ($terms as $term) {
            $tmp = explode(':', $term);
            if (\count($tmp) === 2) {
                $fields[$tmp[0]] = $tmp[1];
            } else {
                $finalTerm[] = $term;
            }
        }

        $this->term = implode(' ', $finalTerm);
        $this->fields = $fields;
    }

    public function hasSearchField(string $name): bool
    {
        return \array_key_exists($name, $this->fields);
    }

    public function getSearchField(string $name): ?string
    {
        if (!$this->hasSearchField($name)) {
            return null;
        }

        return $this->fields[$name];
    }

    public function getSearchFields(): array
    {
        return $this->fields;
    }

    public function getSearchTerm(): ?string
    {
        return $this->term;
    }

    public function hasSearchTerm(): bool
    {
        return !empty($this->term);
    }

    public function getOriginalSearch(): string
    {
        return $this->originalTerm;
    }

    public function __toString()
    {
        return $this->originalTerm;
    }
}

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
    private string $term;
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
                $finalTerm[] = $part->getTerm();
            }
            $this->parts[] = $part;
        }

        $this->term = implode(' ', $finalTerm);
    }

    /**
     * @deprecated since 2.34.0
     */
    public function hasSearchField(string $name): bool
    {
        @trigger_error('The SearchTerm::hasSearchField() method is deprecated and will be removed with 3.0', E_USER_DEPRECATED);

        foreach ($this->parts as $part) {
            if ($part->getField() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated since 2.34.0
     */
    public function getSearchField(string $name): ?string
    {
        @trigger_error('The SearchTerm::getSearchField() method is deprecated and will be removed with 3.0', E_USER_DEPRECATED);

        foreach ($this->parts as $part) {
            if ($part->getField() === $name) {
                return $part->getTerm();
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getSearchFields(): array
    {
        // TODO deprecated 3.0 - all places that use this method should use the RepositorySearchTrait instead (soft deprecation for plugins)
        $fields = [];
        foreach ($this->parts as $part) {
            if (($field = $part->getField()) !== null) {
                $fields[$field] = $part->getTerm();
            }
        }

        return $fields;
    }

    /**
     * @return SearchTermPart[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function getSearchTerm(): string
    {
        // TODO deprecated 3.0 - all places that use this method should use the RepositorySearchTrait instead (soft deprecation for plugins)
        return $this->term;
    }

    public function hasSearchTerm(): bool
    {
        // TODO refactor and use the parts and check if any part has an emoty field name
        return $this->term !== '';
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

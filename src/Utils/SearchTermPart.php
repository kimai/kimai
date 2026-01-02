<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class SearchTermPart
{
    private string $term;
    private ?string $field = null;
    private bool $excluded = false;

    public function __construct(string $term)
    {
        if (str_contains($term, ':')) {
            $tmp = explode(':', $term, 2);
            // search strings like 'name:' are NOT field searches, but simply terms with a colon
            if (\count($tmp) === 2 && $tmp[1] !== '') {
                $this->field = $tmp[0];
                $term = $tmp[1] !== '""' ? $tmp[1] : '';
            }
        }

        if (\strlen($term) > 1 && $term[0] === '!') {
            $term = substr($term, 1);
            $this->excluded = true;
        }

        $this->term = $term;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function isExcluded(): bool
    {
        return $this->excluded;
    }
}

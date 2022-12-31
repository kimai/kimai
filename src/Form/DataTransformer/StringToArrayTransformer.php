<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<array, string>
 */
final class StringToArrayTransformer implements DataTransformerInterface
{
    /**
     * @param non-empty-string $separator
     */
    public function __construct(private readonly string $separator = ',')
    {
    }

    /**
     * Transforms an array of strings to a string.
     *
     * @param array<string> $value
     * @return string
     */
    public function transform(mixed $value): mixed
    {
        if (empty($value)) {
            return '';
        }

        return implode($this->separator, $value);
    }

    /**
     * Transforms a string to an array of tags.
     *
     * @param string|null $value
     * @return array<string>
     * @throws TransformationFailedException
     */
    public function reverseTransform(mixed $value): mixed
    {
        // check for empty list
        if ('' === $value || null === $value) {
            return [];
        }

        return array_filter(array_unique(array_map('trim', explode($this->separator, $value))));
    }
}

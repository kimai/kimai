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
 * Bridges Kimai's scalar-string Configuration value and the structured
 * endpoints collection rendered in the form.
 *
 * @implements DataTransformerInterface<string, array<int, array<string, mixed>>>
 */
final class JsonEndpointsTransformer implements DataTransformerInterface
{
    /**
     * JSON string from the config table → PHP array the CollectionType binds to.
     *
     * @param mixed $value
     * @return array<int, array<string, mixed>>
     */
    public function transform(mixed $value): array
    {
        $decoded = null;

        if (\is_array($value)) {
            // Already-decoded value passed through (e.g., a PRE_SET_DATA listener on
            // the parent form decoded the string before transformers ran).
            $decoded = $value;
        } elseif ($value === null || $value === '' || $value === '[]') {
            return [];
        } elseif (\is_string($value)) {
            try {
                $decoded = json_decode($value, true, 16, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [];
            }
        }

        if (!\is_array($decoded)) {
            return [];
        }

        // Normalize every entry the same way whether it arrived as a decoded array or
        // a JSON string. This keeps the downstream CollectionType seeing a consistent
        // shape and avoids subtle divergences between code paths.
        $rows = [];
        foreach ($decoded as $entry) {
            if (!\is_array($entry)) {
                continue;
            }
            $rows[] = [
                'url' => \is_string($entry['url'] ?? null) ? $entry['url'] : '',
                'secret' => \is_string($entry['secret'] ?? null) ? $entry['secret'] : '',
                'events' => \is_array($entry['events'] ?? null)
                    ? array_values(array_filter($entry['events'], 'is_string'))
                    : [],
            ];
        }

        return $rows;
    }

    /**
     * Collection form data → JSON string persisted in the config table.
     *
     * @param mixed $value
     */
    public function reverseTransform(mixed $value): string
    {
        if ($value === null) {
            return '[]';
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array of endpoints.');
        }

        $normalized = [];
        foreach (array_values($value) as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $url = \is_string($row['url'] ?? null) ? trim($row['url']) : '';
            if ($url === '') {
                continue;
            }
            $normalized[] = [
                'url' => $url,
                'secret' => \is_string($row['secret'] ?? null) ? $row['secret'] : '',
                'events' => \is_array($row['events'] ?? null)
                    ? array_values(array_filter($row['events'], 'is_string'))
                    : [],
            ];
        }

        try {
            return json_encode($normalized, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new TransformationFailedException('Failed to encode endpoints as JSON: ' . $e->getMessage());
        }
    }
}

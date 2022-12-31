<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Pdf;

/**
 * A simple class that is available in PDF-Renderer context,
 * which can be used to define global renderer options.
 */
final class PdfContext
{
    private array $options = [];

    public function setOption(string $key, string|array|null|bool $value): void
    {
        $this->options[$key] = $value;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key): array|string|null
    {
        if (\array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }
}

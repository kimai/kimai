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
    /**
     * Keys that may be set from inside a (sandboxed) Twig template.
     *
     * Sinks that read the local filesystem inside mPDF (e.g. `associated_files`
     * with a `path` entry, or `additional_xmp_rdf`) must NOT appear here. Those
     * remain reachable via InvoiceModel::setOption() from PHP code.
     */
    private const ALLOWED_KEYS = [
        'filename', 'mode', 'format', 'orientation', 'default_font', 'default_font_size', 'fonts',
        'margin_left', 'margin_right', 'margin_top', 'margin_bottom', 'margin_header', 'margin_footer',
        'setAutoTopMargin', 'setAutoBottomMargin', 'PDFA', 'PDFAauto', 'useActiveForms',
    ];

    private array $options = [];

    public function setOption(string $key, string|int|array|null|bool $value): void
    {
        if (!\in_array($key, self::ALLOWED_KEYS, true)) {
            return;
        }

        $this->options[$key] = $value;
    }

    /**
     * @return array<string|int|array|null|bool>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key): string|int|array|null|bool
    {
        if (\array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }
}

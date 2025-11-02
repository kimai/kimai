<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Pdf;

use App\Constants;
use App\Utils\FileHelper;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

final class MPdfConverter implements HtmlToPdfConverter
{
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly string $cacheDirectory
    )
    {
    }

    /**
     * @param array<string, mixed|array<string, mixed>> $options
     * @return array<string, mixed|array<string, mixed>>
     */
    private function sanitizeOptions(array $options): array
    {
        $filtered = array_filter($options, function ($key): bool {
            $allowed = [
                'mode', 'format', 'default_font_size', 'default_font', 'margin_left', 'margin_right', 'margin_top',
                'margin_bottom', 'margin_header', 'margin_footer', 'orientation', 'fonts', 'associated_files', 'additional_xmp_rdf'
            ];
            if (!\in_array($key, $allowed)) {
                $configs = new ConfigVariables();
                if (!\array_key_exists($key, $configs->getDefaults())) {
                    $fonts = new FontVariables();

                    return \array_key_exists($key, $fonts->getDefaults());
                }
            }

            return true;
        }, ARRAY_FILTER_USE_KEY);

        if (\array_key_exists('tempDir', $filtered)) {
            unset($filtered['tempDir']);
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed|array<string, mixed>> $options
     */
    public function convertToPdf(string $html, array $options = []): string
    {
        $sanitized = array_merge(
            $this->sanitizeOptions($options),
            ['tempDir' => $this->cacheDirectory, 'exposeVersion' => false]
        );

        $mpdf = $this->initMpdf($sanitized);

        // some OS'es do not follow the PHP default settings
        if ((int) \ini_get('pcre.backtrack_limit') < 1000000) {
            @ini_set('pcre.backtrack_limit', '1000000');
        }

        // large amount of data take time
        @ini_set('max_execution_time', '120');

        // reduce the size of content parts that are passed to MPDF, to prevent
        // https://mpdf.github.io/troubleshooting/known-issues.html#blank-pages-or-some-sections-missing
        $parts = explode('<pagebreak>', $html);
        for ($i = 0; $i < \count($parts); $i++) {
            if (stripos($parts[$i], '<!-- CONTENT_PART -->') !== false) {
                $subParts = explode('<!-- CONTENT_PART -->', $parts[$i]);
                foreach ($subParts as $subPart) {
                    $mpdf->WriteHTML($subPart);
                }
            } else {
                $mpdf->WriteHTML($parts[$i]);
            }

            if ($i < \count($parts) - 1) {
                $mpdf->WriteHTML('<pagebreak>');
            }
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * @param array<string, mixed|array<string, mixed>> $options
     */
    private function initMpdf(array $options): Mpdf
    {
        $options['fontDir'] = $this->getFontDirectories();
        $options['fontdata'] = $this->mergeFontData($options);

        $associatedFiles = [];
        if (\array_key_exists('associated_files', $options) && \is_array($options['associated_files'])) {
            $associatedFiles = $options['associated_files'];
            unset($options['associated_files']);
        }

        $additionalXmpRdf = null;
        if (\array_key_exists('additional_xmp_rdf', $options) && \is_string($options['additional_xmp_rdf'])) {
            $additionalXmpRdf = $options['additional_xmp_rdf'];
            unset($options['additional_xmp_rdf']);
        }

        $mpdf = new Mpdf($options);
        $mpdf->creator = Constants::SOFTWARE;

        if (\count($associatedFiles) > 0) {
            $mpdf->SetAssociatedFiles($associatedFiles);
        }

        if ($additionalXmpRdf !== null) {
            $mpdf->SetAdditionalXmpRdf($additionalXmpRdf);
        }

        return $mpdf;
    }

    /**
     * @return array<string>
     */
    private function getFontDirectories(): array
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirectories = $defaultConfig['fontDir'];
        $fontDirectories[] = rtrim($this->fileHelper->getDataDirectory('fonts'), DIRECTORY_SEPARATOR);

        return $fontDirectories;
    }

    /**
     * @param array<string, mixed|array<string, mixed>> $options
     * @return array<string, mixed|array<string, mixed>>
     */
    private function mergeFontData(array $options): array
    {
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // lowercase all font names, otherwise they cannot be loaded
        // see https://github.com/kimai/www.kimai.org/issues/280
        if (\array_key_exists('fonts', $options) && \is_array($options['fonts'])) {
            $fonts = [];
            foreach ($options['fonts'] as $name => $values) {
                $fonts[strtolower($name)] = $values;
            }
            $fontData = array_merge($fontData, $fonts);
        }

        return $fontData;
    }
}

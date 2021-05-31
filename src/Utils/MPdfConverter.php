<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Constants;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class MPdfConverter implements HtmlToPdfConverter
{
    /**
     * @var string
     */
    private $cacheDirectory;

    public function __construct(string $cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
    }

    protected function sanitizeOptions(array $options): array
    {
        $configs = new ConfigVariables();
        $fonts = new FontVariables();
        $allowed = [
            'mode', 'format', 'default_font_size', 'default_font', 'margin_left', 'margin_right', 'margin_top',
            'margin_bottom', 'margin_header', 'margin_footer', 'orientation'
        ];

        $filtered = array_filter($options, function ($key) use ($allowed, $configs, $fonts) {
            if (!\in_array($key, $allowed)) {
                if (!\array_key_exists($key, $configs->getDefaults())) {
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
     * @param string $html
     * @param array $options
     * @return mixed|string
     * @throws \Mpdf\MpdfException
     */
    public function convertToPdf(string $html, array $options = [])
    {
        $options = array_merge(
            $this->sanitizeOptions($options),
            ['tempDir' => $this->cacheDirectory, 'exposeVersion' => false]
        );

        $mpdf = new Mpdf($options);
        $mpdf->creator = Constants::SOFTWARE;

        // some OS do not follow the PHP default settings
        if ((int) ini_get('pcre.backtrack_limit') < 1000000) {
            @ini_set('pcre.backtrack_limit', '1000000');
        }

        // reduce the size of content parts that are passed to MPDF, to prevent
        // https://mpdf.github.io/troubleshooting/known-issues.html#blank-pages-or-some-sections-missing
        $parts = explode('<pagebreak>', $html);
        for ($i = 0; $i < \count($parts); $i++) {
            $mpdf->WriteHTML($parts[$i]);
            if ($i < \count($parts) - 1) {
                $mpdf->WriteHTML('<pagebreak>');
            }
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Constants;
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

    /**
     * @param string $html
     * @return mixed|string
     * @throws \Mpdf\MpdfException
     */
    public function convertToPdf(string $html)
    {
        $mpdf = new Mpdf(['tempDir' => $this->cacheDirectory]);
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

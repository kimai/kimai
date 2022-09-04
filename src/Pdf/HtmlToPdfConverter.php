<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Pdf;

interface HtmlToPdfConverter
{
    /**
     * Returns the binary content of the PDF, which can be saved as file.
     * Throws an exception if conversion fails.
     *
     * @param string $html
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function convertToPdf(string $html, array $options = []): string;
}

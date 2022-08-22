<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

use App\Export\Spreadsheet\Extractor\AnnotationExtractor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class AnnotatedObjectExporter
{
    public function __construct(private SpreadsheetExporter $spreadsheetExporter, private AnnotationExtractor $annotationExtractor)
    {
    }

    public function export(string $class, array $entries): Spreadsheet
    {
        $columns = $this->annotationExtractor->extract($class);

        return $this->spreadsheetExporter->export($columns, $entries);
    }
}

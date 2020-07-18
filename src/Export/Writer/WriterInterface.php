<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface WriterInterface
{
    public function getFileExtension(): string;

    public function getContentType(): string;

    public function save(Spreadsheet $spreadsheet, array $options = []): \SplFileInfo;
}

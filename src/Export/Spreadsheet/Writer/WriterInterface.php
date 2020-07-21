<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface WriterInterface
{
    public function getFileExtension(): string;

    public function getContentType(): string;

    /**
     * Save the given spreadsheet
     *
     * @param Spreadsheet $spreadsheet
     * @param array $options
     * @return \SplFileInfo
     */
    public function save(Spreadsheet $spreadsheet, array $options = []): \SplFileInfo;
}

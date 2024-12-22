<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Export\ExportFilename;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\RendererInterface;
use App\Export\TimesheetExportInterface;
use App\Repository\Query\TimesheetQuery;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

final class XlsxRenderer implements RendererInterface, TimesheetExportInterface
{
    use ExportTrait;

    public function __construct(private readonly SpreadsheetRenderer $spreadsheetRenderer)
    {
    }

    public function getId(): string
    {
        return 'xlsx';
    }

    public function getTitle(): string
    {
        return 'xlsx';
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        return $this->getFileResponse(
            $this->renderFile($exportItems, $query),
            (new ExportFilename($query))->getFilename() . '.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function renderFile(array $exportItems, TimesheetQuery $query): \SplFileInfo
    {
        $filename = @tempnam(sys_get_temp_dir(), 'kimai-export-xlsx');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $spreadsheet = new SpoutSpreadsheet(new Writer());
        $spreadsheet->open($filename);

        $this->spreadsheetRenderer->writeSpreadsheet($spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

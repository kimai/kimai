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
use App\Export\Package\CellFormatter\DateStringFormatter;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\RendererInterface;
use App\Export\TimesheetExportInterface;
use App\Repository\Query\TimesheetQuery;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use Symfony\Component\HttpFoundation\Response;

final class CsvRenderer implements RendererInterface, TimesheetExportInterface
{
    use ExportTrait;

    public function __construct(private readonly SpreadsheetRenderer $spreadsheetRenderer)
    {
    }

    public function getId(): string
    {
        return 'csv';
    }

    public function getTitle(): string
    {
        return 'csv';
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        return $this->getFileResponse(
            $this->renderFile($exportItems, $query),
            (new ExportFilename($query))->getFilename() . '.csv',
            'text/csv'
        );
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function renderFile(array $exportItems, TimesheetQuery $query): \SplFileInfo
    {
        $filename = @tempnam(sys_get_temp_dir(), 'kimai-export-csv');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $options = new Options();
        $options->SHOULD_ADD_BOM = false;

        $spreadsheet = new SpoutSpreadsheet(new Writer($options));
        $spreadsheet->open($filename);

        $this->spreadsheetRenderer->registerFormatter('date', new DateStringFormatter());
        $this->spreadsheetRenderer->writeSpreadsheet($spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

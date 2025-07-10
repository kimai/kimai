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
use App\Export\Package\CellFormatter\DurationPlainFormatter;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\RendererInterface;
use App\Export\TimesheetExportInterface;
use App\Repository\Query\TimesheetQuery;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CsvRenderer implements RendererInterface, TimesheetExportInterface
{
    use ExportTrait;

    private string $id = 'csv';
    private string $title = 'default';
    private ?string $locale = null;

    public function __construct(
        private readonly SpreadsheetRenderer $spreadsheetRenderer,
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTitle(): string
    {
        return $this->title;
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
    private function renderFile(array $exportItems, TimesheetQuery $query): \SplFileInfo
    {
        $filename = @tempnam(sys_get_temp_dir(), 'kimai-export-csv');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $options = new Options();
        $options->SHOULD_ADD_BOM = false;

        $opts = $this->spreadsheetRenderer->getTemplate($query)->getOptions();
        if (\array_key_exists('separator', $opts) && $opts['separator'] === ';') {
            $options->FIELD_DELIMITER = ';';
        }

        $spreadsheet = new SpoutSpreadsheet(new Writer($options), $this->translator, $this->locale ?? $this->spreadsheetRenderer->getTemplate($query)->getLocale());
        $spreadsheet->open($filename);

        $this->spreadsheetRenderer->registerFormatter('date', new DateStringFormatter());
        $this->spreadsheetRenderer->registerFormatter('duration', new DurationPlainFormatter(false));
        $this->spreadsheetRenderer->registerFormatter('duration_seconds', new DurationPlainFormatter(true));
        $this->spreadsheetRenderer->writeSpreadsheet($spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

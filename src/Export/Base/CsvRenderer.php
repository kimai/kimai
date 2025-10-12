<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Export\ColumnConverter;
use App\Export\ExportFilename;
use App\Export\ExportRendererInterface;
use App\Export\Package\CellFormatter\DateStringFormatter;
use App\Export\Package\CellFormatter\DurationPlainFormatter;
use App\Export\Package\SpoutSpreadsheet;
use App\Export\TemplateInterface;
use App\Repository\Query\TimesheetQuery;
use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Exclude]
final class CsvRenderer extends AbstractSpreadsheetRenderer implements ExportRendererInterface
{
    public function __construct(
        private readonly ColumnConverter $columnConverter,
        private readonly TranslatorInterface $translator,
        private readonly TemplateInterface $template,
    )
    {
    }

    public function getType(): string
    {
        return 'csv';
    }

    public function getId(): string
    {
        return $this->template->getId();
    }

    public function getTitle(): string
    {
        return $this->template->getTitle();
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

        $opts = $this->template->getOptions();
        if (\array_key_exists('separator', $opts) && $opts['separator'] === ';') {
            $options->FIELD_DELIMITER = ';';
        }

        $spreadsheet = new SpoutSpreadsheet(new Writer($options), $this->translator, $this->template->getLocale());
        $spreadsheet->open($filename);

        $this->columnConverter->registerFormatter('date', new DateStringFormatter());
        $this->columnConverter->registerFormatter('duration', new DurationPlainFormatter(false));
        $this->columnConverter->registerFormatter('duration_seconds', new DurationPlainFormatter(true));

        $this->writeSpreadsheet($this->columnConverter, $this->template, $spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

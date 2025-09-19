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
use App\Export\Package\SpoutSpreadsheet;
use App\Export\TemplateInterface;
use App\Repository\Query\TimesheetQuery;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Exclude]
final class XlsxRenderer extends AbstractSpreadsheetRenderer implements ExportRendererInterface
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
        return 'xlsx';
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
            (new ExportFilename($query))->getFilename() . '.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    private function renderFile(array $exportItems, TimesheetQuery $query): \SplFileInfo
    {
        $filename = @tempnam(sys_get_temp_dir(), 'kimai-export-xlsx');
        if (false === $filename) {
            throw new \Exception('Could not open temporary file');
        }

        $spreadsheet = new SpoutSpreadsheet(new Writer(), $this->translator, $this->template->getLocale());
        $spreadsheet->open($filename);

        $this->writeSpreadsheet($this->columnConverter, $this->template, $spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

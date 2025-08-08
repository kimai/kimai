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
use Symfony\Contracts\Translation\TranslatorInterface;

final class XlsxRenderer implements RendererInterface, TimesheetExportInterface
{
    use ExportTrait;

    private string $id = 'xlsx';
    private string $title = 'default';
    private ?string $locale = null;

    public function __construct(
        private readonly SpreadsheetRenderer $spreadsheetRenderer,
        private readonly TranslatorInterface $translator,
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

        $spreadsheet = new SpoutSpreadsheet(new Writer(), $this->translator, $this->locale ?? $this->spreadsheetRenderer->getTemplate($query)->getLocale());
        $spreadsheet->open($filename);

        $this->spreadsheetRenderer->writeSpreadsheet($spreadsheet, $exportItems, $query);

        return new \SplFileInfo($filename);
    }
}

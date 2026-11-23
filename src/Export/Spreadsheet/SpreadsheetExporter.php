<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet;

use App\Export\Spreadsheet\CellFormatter\ArrayFormatter;
use App\Export\Spreadsheet\CellFormatter\BooleanFormatter;
use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\CellFormatter\DateFormatter;
use App\Export\Spreadsheet\CellFormatter\DateTimeFormatter;
use App\Export\Spreadsheet\CellFormatter\DurationFormatter;
use App\Export\Spreadsheet\CellFormatter\StringFormatter;
use App\Export\Spreadsheet\CellFormatter\TimeFormatter;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class SpreadsheetExporter
{
    /**
     * @var CellFormatterInterface[]
     */
    private array $formatter = [];

    public function __construct(private readonly TranslatorInterface $translator, private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function registerCellFormatter(string $type, CellFormatterInterface $formatter): void
    {
        $this->formatter[$type] = $formatter;
    }

    /**
     * @param ColumnDefinition[] $columns
     * @param array<object> $entries
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(array $columns, array $entries): Spreadsheet
    {
        $formatter = [
            'datetime' => new DateTimeFormatter(),
            'date' => new DateFormatter(),
            'time' => new TimeFormatter(),
            'duration' => new DurationFormatter(),
            'boolean' => new BooleanFormatter(),
            'array' => new ArrayFormatter(),
            'string' => new StringFormatter(),
        ];

        foreach ($this->formatter as $name => $object) {
            $formatter[$name] = $object;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set default row height to automatic, so we can specify wrap text columns later on
        // without bloating the output file as we would need to store stylesheet info for every cell.
        // LibreOffice is still not considering this flag, @see https://github.com/PHPOffice/PHPExcel/issues/588
        // with no solution implemented so nothing we can do about it there.
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $recordsHeaderColumn = 1;
        $recordsHeaderRow = 1;

        foreach ($columns as $settings) {
            $sheet->setCellValue(CellAddress::fromColumnAndRow($recordsHeaderColumn++, $recordsHeaderRow), $this->translator->trans($settings->getLabel(), [], $settings->getTranslationDomain()));
        }

        $entryHeaderRow = $recordsHeaderRow + 1;

        foreach ($entries as $entry) {
            $entryHeaderColumn = 1;

            foreach ($columns as $settings) {
                $permissions = $settings->getPermissions();
                $allow = \count($permissions) === 0;

                foreach ($permissions as $permission) {
                    if ($this->authorizationChecker->isGranted($permission, $entry)) {
                        $allow = true;
                        break;
                    }
                }

                $type = $settings->getType();

                if ($allow) {
                    $value = \call_user_func($settings->getAccessor(), $entry);
                } else {
                    // the column stays, but the value is replaced by an empty cell.
                    // the type is dropped on purpose: a type specific default like the
                    // "0:00" of a duration would read like a real value instead of a hidden one
                    $value = null;
                    $type = 'string';
                }

                if (!\array_key_exists($type, $formatter)) {
                    $sheet->setCellValue(CellAddress::fromColumnAndRow($entryHeaderColumn, $entryHeaderRow), $value);
                } else {
                    $formatter[$type]->setFormattedValue($sheet, $entryHeaderColumn, $entryHeaderRow, $value);
                }

                $entryHeaderColumn++;
            }

            $entryHeaderRow++;
        }

        return $spreadsheet;
    }
}

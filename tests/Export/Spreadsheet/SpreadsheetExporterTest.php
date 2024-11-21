<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet;

use App\Entity\Project;
use App\Export\Spreadsheet\CellFormatter\CellFormatterInterface;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\SpreadsheetExporter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Export\Spreadsheet\SpreadsheetExporter
 */
class SpreadsheetExporterTest extends TestCase
{
    public function testExport(): void
    {
        $sut = new SpreadsheetExporter($this->createMock(TranslatorInterface::class));
        $sut->registerCellFormatter('foo', new class() implements CellFormatterInterface {
            public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void
            {
                if (!\is_scalar($value)) {
                    throw new \InvalidArgumentException('Only scalar values are supported');
                }
                $sheet->setCellValue([$column, $row], '##' . $value . '##');
            }
        });
        $sut->registerCellFormatter('bar', new class() implements CellFormatterInterface {
            public function setFormattedValue(Worksheet $sheet, int $column, int $row, $value): void
            {
                if (!\is_scalar($value)) {
                    throw new \InvalidArgumentException('Only scalar values are supported');
                }
                $sheet->setCellValue([$column, $row], '~' . $value . '~');
            }
        });

        $project = new Project();
        $project->setName('test project');
        $project->setVisible(false);

        $columns = [
            new ColumnDefinition('test1', 'foo', function (Project $project) {
                return $project->getName();
            }),
            new ColumnDefinition('test2', 'bar', function (Project $project) {
                return $project->getName();
            }),
            new ColumnDefinition('test3', 'boolean', function (Project $project) {
                return $project->isVisible();
            }),
        ];

        $entries = [
            $project
        ];

        $spreadsheet = $sut->export($columns, $entries);

        $worksheet = $spreadsheet->getActiveSheet();

        self::assertEquals('##test project##', $worksheet->getCell([1, 2])->getValue());
        self::assertEquals('~test project~', $worksheet->getCell([2, 2])->getValue());
        self::assertFalse($worksheet->getCell([3, 2])->getValue());
    }
}

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
use App\Tests\Mocks\AuthorizationCheckerFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SpreadsheetExporter::class)]
class SpreadsheetExporterTest extends TestCase
{
    public function testExport(): void
    {
        $sut = new SpreadsheetExporter($this->createMock(TranslatorInterface::class), (new AuthorizationCheckerFactory($this))->create());
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

    public function testColumnWithoutPermissionIsEmpty(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $sut = new SpreadsheetExporter($translator, (new AuthorizationCheckerFactory($this))->create(false, ['time' => true]));

        $columns = [
            // no permissions at all: always exported
            new ColumnDefinition('name', 'string', fn () => 'a name'),
            // granted
            $granted = new ColumnDefinition('timeBudget', 'duration', fn () => 12345),
            // denied, and a "duration" so the type specific default would be visible
            $deniedDuration = new ColumnDefinition('otherDuration', 'duration', fn () => 12345),
            // denied, one of both permissions has to be granted
            $deniedFloat = new ColumnDefinition('budget', 'float', fn () => 999.99),
        ];
        $granted->setPermissions(['time']);
        $deniedDuration->setPermissions(['budget']);
        $deniedFloat->setPermissions(['budget', 'another']);

        $spreadsheet = $sut->export($columns, [new \stdClass()]);
        $sheet = $spreadsheet->getActiveSheet();

        self::assertEquals('a name', $sheet->getCell([1, 2])->getValue());
        self::assertEquals('=12345/86400', $sheet->getCell([2, 2])->getValue());

        // a hidden duration must not fall back to "0:00", it has to stay empty
        self::assertEquals('', $sheet->getCell([3, 2])->getValue());
        self::assertEquals('', $sheet->getCell([4, 2])->getValue());

        // the headers stay in place, only the values are hidden
        self::assertEquals('otherDuration', $sheet->getCell([3, 1])->getValue());
        self::assertEquals('budget', $sheet->getCell([4, 1])->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    public function testColumnWithOneGrantedPermissionIsExported(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        // only the second permission is granted, which is enough
        $sut = new SpreadsheetExporter($translator, (new AuthorizationCheckerFactory($this))->create(false, ['time' => true]));

        $column = new ColumnDefinition('budgetType', 'string', fn () => 'month');
        $column->setPermissions(['budget', 'time']);

        $spreadsheet = $sut->export([$column], [new \stdClass()]);
        self::assertEquals('month', $spreadsheet->getActiveSheet()->getCell([1, 2])->getValue());

        $spreadsheet->disconnectWorksheets();
    }
}

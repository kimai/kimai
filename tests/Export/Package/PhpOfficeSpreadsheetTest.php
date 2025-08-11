<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Package;

use App\Export\Package\CellFormatter\DefaultFormatter;
use App\Export\Package\Column;
use App\Export\Package\PhpOfficeSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(PhpOfficeSpreadsheet::class)]
class PhpOfficeSpreadsheetTest extends TestCase
{
    private string $filename;
    private int $counter = 1;

    protected function setUp(): void
    {
        $this->filename = realpath(__DIR__ . '/../../_data/') . '/test' . $this->counter++ . '.xlsx';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    private function createSut(): PhpOfficeSpreadsheet
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new PhpOfficeSpreadsheet($translator);
    }

    public function testopenSetsFilename(): void
    {
        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $reflection = new \ReflectionClass($spreadsheetPackage);
        $property = $reflection->getProperty('filename');
        $property->setAccessible(true);
        self::assertEquals($this->filename, $property->getValue($spreadsheetPackage));
    }

    public function testsaveThrowsExceptionWhenFilenameIsNull(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Need to call open() first before save()');

        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->save();
    }

    public function testsaveWritesFile(): void
    {
        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->setColumns([new Column('Foo', new DefaultFormatter()), new Column('Bar', new DefaultFormatter())]);
        $spreadsheetPackage->addRow(['Data1', 'Data2']);
        $spreadsheetPackage->addRow(['Data3', 'Data4']);
        $spreadsheetPackage->save();

        self::assertGreaterThan(0, filesize($this->filename));
    }

    public function testsaveThrowsExceptionWhenSpreadsheetIsNull(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot re-use spreadsheet after calling save()');

        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->save();
        $spreadsheetPackage->save();
    }

    public function testsetHeaderSetsHeaderRow(): void
    {
        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->setColumns([new Column('Column1', new DefaultFormatter()), new Column('Column2', new DefaultFormatter())]);

        $reflection = new \ReflectionClass($spreadsheetPackage);
        $property = $reflection->getProperty('worksheet');
        $property->setAccessible(true);
        /** @var Worksheet $worksheet */
        $worksheet = $property->getValue($spreadsheetPackage);

        self::assertNotNull($worksheet);
        self::assertEquals('Column1', $worksheet->getCell('A1')->getValue());
        self::assertEquals('Column2', $worksheet->getCell('B1')->getValue());
    }

    public function testaddRowAddsDataRow(): void
    {
        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->addRow(['Data1', 'Data2']);

        $reflection = new \ReflectionClass($spreadsheetPackage);
        $property = $reflection->getProperty('worksheet');
        $property->setAccessible(true);
        /** @var Worksheet $worksheet */
        $worksheet = $property->getValue($spreadsheetPackage);

        self::assertEquals('Data1', $worksheet->getCell('A1')->getValue());
        self::assertEquals('Data2', $worksheet->getCell('B1')->getValue());
    }

    public function testaddRowThrowsExceptionWhenSpreadsheetIsNull(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot re-use spreadsheet after calling save()');

        $spreadsheetPackage = $this->createSut();
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->save();
        $spreadsheetPackage->addRow(['Data1', 'Data2']);
    }
}

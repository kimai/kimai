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
use App\Export\Package\SpoutSpreadsheet;
use OpenSpout\Writer\CSV\Writer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SpoutSpreadsheet::class)]
class SpoutSpreadsheetTest extends TestCase
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

    public function testsaveWritesFile(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $spreadsheetPackage = new SpoutSpreadsheet(new Writer(), $translator);
        $spreadsheetPackage->open($this->filename);
        $spreadsheetPackage->setColumns([new Column('Foo', new DefaultFormatter()), new Column('Bar', new DefaultFormatter())]);
        $spreadsheetPackage->addRow(['Data1', 'Data2']);
        $spreadsheetPackage->addRow(['Data3', 'Data4']);
        $spreadsheetPackage->save();

        self::assertGreaterThan(0, filesize($this->filename));
    }
}

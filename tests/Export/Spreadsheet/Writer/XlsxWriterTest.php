<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Writer;

use App\Export\Spreadsheet\Writer\XlsxWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Spreadsheet\Writer\XlsxWriter
 */
class XlsxWriterTest extends TestCase
{
    public function testWriter()
    {
        $sut = new XlsxWriter();

        self::assertEquals('xlsx', $sut->getFileExtension());
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $sut->getContentType());

        $spreadsheet = new Spreadsheet();

        $file = $sut->save($spreadsheet);
        self::assertInstanceOf(\SplFileInfo::class, $file);
        self::assertTrue(file_exists($file->getRealPath()));

        // TODO test autofilter
        // TODO test freeze pane
    }
}

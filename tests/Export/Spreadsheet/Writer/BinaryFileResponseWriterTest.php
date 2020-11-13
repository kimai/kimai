<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Writer;

use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\Spreadsheet\Writer\BinaryFileResponseWriter
 */
class BinaryFileResponseWriterTest extends TestCase
{
    public function testSave()
    {
        $sut = new BinaryFileResponseWriter(new XlsxWriter(), 'foobar');

        self::assertEquals('xlsx', $sut->getFileExtension());
        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $sut->getContentType());

        $spreadsheet = new Spreadsheet();

        $file = $sut->save($spreadsheet);
        self::assertInstanceOf(\SplFileInfo::class, $file);
        self::assertTrue(file_exists($file->getRealPath()));
    }

    public function testGetResponse()
    {
        $sut = new BinaryFileResponseWriter(new XlsxWriter(), 'foobar');

        $spreadsheet = new Spreadsheet();

        $response = $sut->getFileResponse($spreadsheet);

        self::assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment; filename=foobar', $response->headers->get('Content-Disposition'));

        $file = $response->getFile();
        self::assertTrue(file_exists($file->getRealPath()));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        self::assertNotEmpty($content2);

        self::assertFalse(file_exists($file->getRealPath()));
    }
}

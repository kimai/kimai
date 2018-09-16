<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\CsvRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\CsvRenderer
 */
class CsvRendererTest extends AbstractRendererTest
{
    public function testSupports()
    {
        $sut = $this->getAbstractRenderer(CsvRenderer::class);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }

    public function testRender()
    {
        /** @var CsvRenderer $sut */
        $sut = $this->getAbstractRenderer(CsvRenderer::class);
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('export.csv');
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $file = $response->getFile();
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=export.csv', $response->headers->get('Content-Disposition'));

        $content = file_get_contents($file->getRealPath());
        $this->assertNotContains('${', $content);
        $this->assertContains('', $content);
        $this->assertEquals(6, substr_count($content, PHP_EOL));
        $this->assertEquals(5, substr_count($content, 'activity description'));
        $this->assertEquals(1, substr_count($content, ',"kevin",'));
        $this->assertEquals(2, substr_count($content, ',"hello-world",'));
        $this->assertEquals(2, substr_count($content, ',"foo-bar",'));
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\DocxRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\DocxRenderer
 * @covers \App\Invoice\Renderer\AbstractRenderer
 */
class DocxRendererTest extends AbstractRendererTest
{
    public function testSupports()
    {
        $sut = $this->getAbstractRenderer(DocxRenderer::class);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }

    public function testRender()
    {
        /** @var DocxRenderer $sut */
        $sut = $this->getAbstractRenderer(DocxRenderer::class);
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('company.docx');
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $file = $response->getFile();
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=company.docx', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));

        // TODO test document content?
        /*
        $content = file_get_contents($file->getRealPath());
        $this->assertNotContains('${', $content);
        $this->assertContains(',"1,947.99" ', $content);
        $this->assertEquals(6, substr_count($content, PHP_EOL));
        $this->assertEquals(5, substr_count($content, 'activity description'));
        $this->assertEquals(1, substr_count($content, ',"kevin",'));
        $this->assertEquals(2, substr_count($content, ',"hello-world",'));
        $this->assertEquals(2, substr_count($content, ',"foo-bar",'));
        */

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        $this->assertNotEmpty($content2);

        $this->assertFalse(file_exists($file->getRealPath()));
    }
}

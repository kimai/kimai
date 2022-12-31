<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\DocxRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\DocxRenderer
 * @covers \App\Invoice\Renderer\AbstractRenderer
 * @group integration
 */
class DocxRendererTest extends TestCase
{
    use RendererTestTrait;

    public function testSupports()
    {
        $sut = $this->getAbstractRenderer(DocxRenderer::class);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('invoice.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('service-date.pdf.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('company.docx', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx', true)));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods', true)));
    }

    public function testRender()
    {
        /** @var DocxRenderer $sut */
        $sut = $this->getAbstractRenderer(DocxRenderer::class);
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('company.docx', true);
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $filename = $model->getInvoiceNumber() . '-customer_with_special_name.docx';
        $file = $response->getFile();
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=' . $filename, $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));

        ob_start();
        $response->sendContent();
        $content2 = ob_get_clean();
        $this->assertNotEmpty($content2);

        $this->assertFalse(file_exists($file->getRealPath()));
    }
}

<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Renderer;

use App\Invoice\Renderer\OdsRenderer;
use App\Model\InvoiceModel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @covers \App\Invoice\Renderer\OdsRenderer
 * @covers \App\Invoice\Renderer\AbstractRenderer
 * @covers \App\Invoice\Renderer\AbstractSpreadsheetRenderer
 */
class OdsRendererTest extends AbstractRendererTest
{
    public function testSupports()
    {
        $sut = $this->getAbstractRenderer(OdsRenderer::class);

        $this->assertFalse($sut->supports($this->getInvoiceDocument('default.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('freelancer.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('timesheet.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('foo.html.twig')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('company.docx')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('export.csv')));
        $this->assertFalse($sut->supports($this->getInvoiceDocument('spreadsheet.xlsx')));
        $this->assertTrue($sut->supports($this->getInvoiceDocument('open-spreadsheet.ods')));
    }

    public function getTestModel()
    {
        yield [$this->getInvoiceModel(), '1,947.99', 6, 5, 1, 2, 2];
        yield [$this->getInvoiceModelOneEntry(), '293.27', 2, 1, 0, 1, 0];
    }

    /**
     * @dataProvider getTestModel
     */
    public function testRender(InvoiceModel $model, $expectedRate, $expectedRows, $expectedDescriptions, $expectedUser1, $expectedUser2, $expectedUser3)
    {
        /** @var OdsRenderer $sut */
        $sut = $this->getAbstractRenderer(OdsRenderer::class);
        $model = $this->getInvoiceModel();
        $document = $this->getInvoiceDocument('open-spreadsheet.ods');
        /** @var BinaryFileResponse $response */
        $response = $sut->render($document, $model);

        $file = $response->getFile();
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=open-spreadsheet.ods', $response->headers->get('Content-Disposition'));

        $this->assertTrue(file_exists($file->getRealPath()));

        // TODO test spreadsheet content?
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
